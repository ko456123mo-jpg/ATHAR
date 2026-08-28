<?php

namespace Tests\Feature\Forensics;

use App\Models\AnalysisJob;
use App\Models\Evidence;
use App\Models\ForensicTool;
use App\Models\InvestigationCase;
use App\Models\ToolOperation;
use App\Models\User;
use Database\Seeders\ToolRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireAnalysisJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_command_times_out_only_overdue_jobs_and_records_provenance(): void
    {
        $this->seed(ToolRegistrySeeder::class);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-EXPIRE-001', 'name' => 'Timeout control', 'created_by' => $investigator->id]);
        $evidence = Evidence::query()->create(['case_id' => $case->id, 'evidence_number' => 'EVD-EXPIRE-001', 'name' => 'Controlled PCAP', 'type' => 'pcap', 'storage_key' => 'evidence/expire/original.pcap', 'working_copy_key' => 'evidence/expire/working/input.pcap', 'size_bytes' => 8, 'status' => 'verified', 'uploaded_by' => $investigator->id]);
        $tool = ForensicTool::query()->where('slug', 'tshark')->firstOrFail();
        $operation = ToolOperation::query()->where('tool_id', $tool->id)->where('operation_key', 'packet_summary')->firstOrFail();
        $operation->update(['timeout_seconds' => 60]);
        $expired = AnalysisJob::query()->create(['public_id' => 'bb0e215b-417d-4a66-b17c-1184eb0e8001', 'case_id' => $case->id, 'evidence_id' => $evidence->id, 'tool_id' => $tool->id, 'operation_id' => $operation->id, 'status' => 'running', 'requested_by' => $investigator->id, 'started_at' => now()->subSeconds(61)]);
        $fresh = AnalysisJob::query()->create(['public_id' => 'bb0e215b-417d-4a66-b17c-1184eb0e8002', 'case_id' => $case->id, 'evidence_id' => $evidence->id, 'tool_id' => $tool->id, 'operation_id' => $operation->id, 'status' => 'running', 'requested_by' => $investigator->id, 'started_at' => now()->subSeconds(30)]);

        $this->artisan('athar:expire-analysis-jobs')->expectsOutput('Expired 1 analysis job(s).')->assertExitCode(0);

        $this->assertDatabaseHas('analysis_jobs', ['id' => $expired->id, 'status' => 'timed_out', 'error_message' => 'Platform execution timeout exceeded.']);
        $this->assertDatabaseHas('analysis_jobs', ['id' => $fresh->id, 'status' => 'running']);
        $this->assertDatabaseHas('chain_of_custody', ['evidence_id' => $evidence->id, 'event_type' => 'analysis_timed_out']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'analysis.job_timed_out', 'entity_id' => $expired->id]);
    }
}
