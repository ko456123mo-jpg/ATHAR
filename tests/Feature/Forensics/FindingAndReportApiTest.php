<?php

namespace Tests\Feature\Forensics;

use App\Models\Evidence;
use App\Models\InvestigationCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FindingAndReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_finding_review_and_report_generation_write_audit_and_custody_records(): void
    {
        Storage::fake('private');
        $investigator = User::factory()->create(['role' => 'investigator']);
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-REPORT', 'name' => 'Report test', 'created_by' => $investigator->id]);
        $case->members()->attach([$investigator->id => ['role_in_case' => 'lead'], $reviewer->id => ['role_in_case' => 'reviewer']]);
        $evidence = Evidence::query()->create(['case_id' => $case->id, 'evidence_number' => 'EVD-REPORT', 'name' => 'Sample PCAP', 'type' => 'pcap', 'storage_key' => 'evidence/report/input.pcap', 'size_bytes' => 4, 'status' => 'verified', 'uploaded_by' => $investigator->id]);

        $finding = $this->actingAs($investigator)->postJson('/api/v1/cases/'.$case->id.'/findings', [
            'title' => 'DNS traffic observed', 'severity' => 'medium', 'description' => 'A benign test finding for report generation.', 'evidence_id' => $evidence->id,
        ])->assertCreated()->json();

        $this->actingAs($reviewer)->postJson('/api/v1/findings/'.$finding['id'].'/review', ['status' => 'reviewed'])->assertOk();
        $report = $this->actingAs($investigator)->postJson('/api/v1/cases/'.$case->id.'/reports')->assertCreated()->json();
        $this->actingAs($reviewer)->postJson('/api/v1/reports/'.$report['id'].'/approve')->assertOk()->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('audit_logs', ['action' => 'finding.reviewed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.generated']);
        $this->assertDatabaseHas('chain_of_custody', ['event_type' => 'report_approved']);
        Storage::disk('private')->assertExists($report['file_key']);
    }
}
