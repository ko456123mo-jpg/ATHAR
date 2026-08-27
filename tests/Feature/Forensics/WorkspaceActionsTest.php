<?php

namespace Tests\Feature\Forensics;

use App\Models\Evidence;
use App\Models\Finding;
use App\Models\ForensicReport;
use App\Models\ForensicTool;
use App\Models\InvestigationCase;
use App\Models\ToolOperation;
use App\Models\User;
use Database\Seeders\ToolRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkspaceActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_investigator_can_use_practical_case_evidence_working_copy_and_job_actions(): void
    {
        Storage::fake('private');
        $this->seed(ToolRegistrySeeder::class);
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($investigator)->post('/workspace/cases', [
            'name' => 'Safe training investigation',
            'priority' => 'high',
            'description' => 'A controlled non-production workflow test.',
        ])->assertRedirect('/workspace/cases');
        $case = InvestigationCase::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'case.created', 'entity_id' => $case->id]);

        $this->actingAs($investigator)->post('/workspace/evidence', [
            'case_id' => $case->id,
            'name' => 'Benign capture',
            'type' => 'pcap',
            'source' => 'controlled fixture',
            'evidence' => UploadedFile::fake()->create('benign.pcap', 4, 'application/vnd.tcpdump.pcap'),
        ])->assertRedirect('/workspace/evidence');
        $evidence = Evidence::query()->firstOrFail();
        $this->assertDatabaseCount('evidence_hashes', 1);

        $this->actingAs($investigator)->post('/workspace/evidence/'.$evidence->id.'/working-copy')->assertRedirect('/workspace/evidence');
        $evidence->refresh();
        $this->assertNotNull($evidence->working_copy_key);
        Storage::disk('private')->assertExists($evidence->working_copy_key);

        $tool = ForensicTool::query()->where('slug', 'tshark')->firstOrFail();
        $operation = ToolOperation::query()->where('tool_id', $tool->id)->where('operation_key', 'packet_summary')->firstOrFail();
        $this->actingAs($investigator)->post('/workspace/jobs', [
            'case_id' => $case->id,
            'evidence_id' => $evidence->id,
            'tool_id' => $tool->id,
            'operation_id' => $operation->id,
        ])->assertRedirect('/workspace/jobs');

        $this->assertDatabaseHas('analysis_jobs', ['case_id' => $case->id, 'evidence_id' => $evidence->id, 'status' => 'queued']);
        $this->assertDatabaseHas('chain_of_custody', ['evidence_id' => $evidence->id, 'event_type' => 'analysis_requested']);
    }

    public function test_finding_review_and_report_actions_reuse_the_provenance_workflow(): void
    {
        Storage::fake('private');
        $investigator = User::factory()->create(['role' => 'investigator']);
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-WEB-REVIEW', 'name' => 'Reviewed workflow', 'created_by' => $investigator->id]);
        $case->members()->attach([$investigator->id => ['role_in_case' => 'lead'], $reviewer->id => ['role_in_case' => 'reviewer']]);
        $evidence = Evidence::query()->create(['case_id' => $case->id, 'evidence_number' => 'EVD-WEB-REVIEW', 'name' => 'Controlled item', 'type' => 'document', 'storage_key' => 'evidence/web-review.txt', 'size_bytes' => 12, 'status' => 'verified', 'uploaded_by' => $investigator->id]);

        $this->actingAs($investigator)->post('/workspace/findings', [
            'case_id' => $case->id,
            'title' => 'Controlled observation',
            'severity' => 'informational',
            'description' => 'This finding is introduced solely to verify the protected workflow.',
        ])->assertRedirect('/workspace/findings');
        $finding = Finding::query()->firstOrFail();

        $this->actingAs($reviewer)->post('/workspace/findings/'.$finding->id.'/review', ['status' => 'reviewed'])->assertRedirect('/workspace/findings');
        $this->assertDatabaseHas('findings', ['id' => $finding->id, 'status' => 'reviewed', 'reviewed_by' => $reviewer->id]);

        $this->actingAs($investigator)->post('/workspace/reports', ['case_id' => $case->id])->assertRedirect('/workspace/reports');
        $report = ForensicReport::query()->firstOrFail();
        Storage::disk('private')->assertExists($report->file_key);

        $this->actingAs($reviewer)->post('/workspace/reports/'.$report->id.'/approve')->assertRedirect('/workspace/reports');
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'approved', 'approved_by' => $reviewer->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.approved', 'entity_id' => $report->id]);
        $this->assertDatabaseHas('chain_of_custody', ['evidence_id' => $evidence->id, 'event_type' => 'report_approved']);
    }
}
