<?php

namespace Tests\Feature\Forensics;

use App\Models\Evidence;
use App\Models\ForensicAgent;
use App\Models\ForensicTool;
use App\Models\InvestigationCase;
use App\Models\ToolOperation;
use App\Models\User;
use Database\Seeders\ToolRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysisJobAndAgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowlisted_tshark_job_is_claimed_and_completed_by_authenticated_agent(): void
    {
        Storage::fake('private');
        $this->seed(ToolRegistrySeeder::class);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-JOB-001', 'name' => 'TShark POC', 'created_by' => $investigator->id]);
        $case->members()->attach($investigator->id, ['role_in_case' => 'lead']);
        $evidence = Evidence::query()->create([
            'case_id' => $case->id,
            'evidence_number' => 'EVD-JOB-001',
            'name' => 'Benign PCAP',
            'type' => 'pcap',
            'storage_key' => 'evidence/1/EVD-JOB-001/original.pcap',
            'size_bytes' => 8,
            'status' => 'verified',
            'uploaded_by' => $investigator->id,
        ]);
        Storage::disk('private')->put($evidence->storage_key, 'pcapdata');
        $evidence->update(['working_copy_key' => 'working-copies/1/EVD-JOB-001/analysis.pcap']);
        Storage::disk('private')->put($evidence->working_copy_key, 'pcapdata');
        $tool = ForensicTool::query()->where('slug', 'tshark')->firstOrFail();
        $operation = ToolOperation::query()->where('tool_id', $tool->id)->where('operation_key', 'packet_summary')->firstOrFail();

        $create = $this->actingAs($investigator)->postJson('/api/v1/cases/'.$case->id.'/analysis-jobs', [
            'evidence_id' => $evidence->id,
            'tool_id' => $tool->id,
            'operation_id' => $operation->id,
            'parameters' => [],
        ]);
        $create->assertAccepted()->assertJsonPath('status', 'queued');
        $jobId = $create->json('public_id');

        $token = 'agent-test-token';
        $agent = ForensicAgent::query()->create(['name' => 'caine-test', 'token_hash' => Hash::make($token), 'status' => 'offline']);
        $this->withToken($token)->postJson('/api/v1/agent/heartbeat', ['hostname' => 'caine-vbox-test'])->assertOk();
        $claim = $this->withToken($token)->postJson('/api/v1/agent/jobs/claim');
        $claim->assertOk()->assertJsonPath('job_id', $jobId)->assertJsonPath('tool_id', 'tshark')->assertJsonPath('operation_key', 'packet_summary');

        $this->withToken($token)->postJson('/api/v1/agent/jobs/'.$jobId.'/status', ['status' => 'running'])->assertOk()->assertJsonPath('status', 'running');
        $result = $this->withToken($token)->postJson('/api/v1/agent/jobs/'.$jobId.'/result', [
            'raw_output' => "frame.number,frame.time_epoch\n1,1720000000.0",
            'parsed_result' => [['frame_number' => 1, 'timestamp' => '1720000000.0', 'protocol' => 'DNS']],
            'tool_version' => '4.x-test',
            'adapter_version' => '0.1.0',
            'exit_code' => 0,
        ]);
        $result->assertCreated()->assertJsonPath('tool_version', '4.x-test');

        $this->assertDatabaseHas('analysis_jobs', ['public_id' => $jobId, 'agent_id' => $agent->id, 'status' => 'completed']);
        $this->assertDatabaseCount('analysis_results', 1);
        $this->assertDatabaseHas('chain_of_custody', ['event_type' => 'analysis_requested']);
        $this->assertDatabaseHas('chain_of_custody', ['event_type' => 'analysis_result_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'agent.result_submitted']);
    }

    public function test_agent_endpoint_rejects_missing_or_invalid_token(): void
    {
        $this->postJson('/api/v1/agent/heartbeat')->assertUnauthorized();
        $this->withToken('invalid-agent-token')->postJson('/api/v1/agent/heartbeat')->assertUnauthorized();
    }

    public function test_agent_result_submission_rejects_oversized_or_malformed_data_and_foreign_agent_access(): void
    {
        Storage::fake('private');
        $this->seed(ToolRegistrySeeder::class);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-JOB-NEGATIVE', 'name' => 'Negative API test', 'created_by' => $investigator->id]);
        $case->members()->attach($investigator->id, ['role_in_case' => 'lead']);
        $evidence = Evidence::query()->create(['case_id' => $case->id, 'evidence_number' => 'EVD-NEGATIVE', 'name' => 'PCAP', 'type' => 'pcap', 'storage_key' => 'evidence/test.pcap', 'size_bytes' => 8, 'status' => 'verified', 'uploaded_by' => $investigator->id]);
        $evidence->update(['working_copy_key' => 'working-copies/test.pcap']);
        Storage::disk('private')->put($evidence->working_copy_key, 'pcapdata');
        $tool = ForensicTool::query()->where('slug', 'tshark')->firstOrFail();
        $operation = ToolOperation::query()->where('tool_id', $tool->id)->where('operation_key', 'packet_summary')->firstOrFail();
        $operation->update(['max_output_bytes' => 10]);
        $jobId = $this->actingAs($investigator)->postJson('/api/v1/cases/'.$case->id.'/analysis-jobs', ['evidence_id' => $evidence->id, 'tool_id' => $tool->id, 'operation_id' => $operation->id, 'parameters' => []])->json('public_id');
        $firstToken = 'first-agent-token';
        $secondToken = 'second-agent-token';
        ForensicAgent::query()->create(['name' => 'caine-one', 'token_hash' => Hash::make($firstToken), 'status' => 'online']);
        ForensicAgent::query()->create(['name' => 'caine-two', 'token_hash' => Hash::make($secondToken), 'status' => 'online']);
        $this->withToken($firstToken)->postJson('/api/v1/agent/jobs/claim')->assertOk();
        $payload = ['raw_output' => str_repeat('x', 11), 'parsed_result' => [], 'tool_version' => '4.0', 'adapter_version' => '0.1.0', 'exit_code' => 0];
        $this->withToken($firstToken)->postJson('/api/v1/agent/jobs/'.$jobId.'/result', $payload)->assertUnprocessable();
        $payload['raw_output'] = 'ok';
        $payload['parsed_result'] = 'not-an-array';
        $this->withToken($firstToken)->postJson('/api/v1/agent/jobs/'.$jobId.'/result', $payload)->assertUnprocessable();
        $payload['parsed_result'] = [];
        $this->withToken($secondToken)->postJson('/api/v1/agent/jobs/'.$jobId.'/result', $payload)->assertForbidden();
    }

    public function test_analysis_job_requires_an_existing_working_copy_and_never_exposes_original_evidence(): void
    {
        Storage::fake('private');
        $this->seed(ToolRegistrySeeder::class);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-WORKING-COPY', 'name' => 'Working copy required', 'created_by' => $investigator->id]);
        $case->members()->attach($investigator->id, ['role_in_case' => 'lead']);
        $evidence = Evidence::query()->create(['case_id' => $case->id, 'evidence_number' => 'EVD-WORKING-COPY', 'name' => 'PCAP', 'type' => 'pcap', 'storage_key' => 'evidence/original.pcap', 'size_bytes' => 8, 'status' => 'verified', 'uploaded_by' => $investigator->id]);
        $tool = ForensicTool::query()->where('slug', 'tshark')->firstOrFail();
        $operation = ToolOperation::query()->where('tool_id', $tool->id)->where('operation_key', 'packet_summary')->firstOrFail();

        $this->actingAs($investigator)->postJson('/api/v1/cases/'.$case->id.'/analysis-jobs', ['evidence_id' => $evidence->id, 'tool_id' => $tool->id, 'operation_id' => $operation->id])->assertUnprocessable();
        $evidence->update(['working_copy_key' => 'working-copies/EVD-WORKING-COPY/analysis.pcap']);
        Storage::disk('private')->put($evidence->working_copy_key, 'pcapdata');
        $jobId = $this->actingAs($investigator)->postJson('/api/v1/cases/'.$case->id.'/analysis-jobs', ['evidence_id' => $evidence->id, 'tool_id' => $tool->id, 'operation_id' => $operation->id])->assertAccepted()->json('public_id');
        $token = 'working-copy-agent-token';
        ForensicAgent::query()->create(['name' => 'caine-working-copy', 'token_hash' => Hash::make($token), 'status' => 'online']);

        $this->withToken($token)->postJson('/api/v1/agent/jobs/claim')->assertOk()->assertJsonPath('job_id', $jobId)->assertJsonPath('evidence_reference', $evidence->working_copy_key);
    }
}
