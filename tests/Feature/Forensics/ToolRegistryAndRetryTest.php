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

class ToolRegistryAndRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_advance_tshark_through_verified_states(): void
    {
        $this->seed(ToolRegistrySeeder::class);
        $admin = User::factory()->create(['role' => 'administrator']);
        $tool = ForensicTool::query()->where('slug', 'tshark')->firstOrFail();

        $this->actingAs($admin)->postJson('/api/v1/tools/'.$tool->id.'/verification', ['verification_state' => 'configured'])->assertOk()->assertJsonPath('verification_state', 'configured');
        $this->actingAs($admin)->postJson('/api/v1/tools/'.$tool->id.'/verification', ['verification_state' => 'installed', 'version' => '4.4.0', 'path' => '/usr/bin/tshark'])->assertOk()->assertJsonPath('verification_state', 'installed');
        $this->actingAs($admin)->postJson('/api/v1/tools/'.$tool->id.'/verification', ['verification_state' => 'end_to_end_verified', 'version' => '4.4.0', 'path' => '/usr/bin/tshark'])->assertUnprocessable();
    }

    public function test_failed_job_can_only_retry_before_reaching_maximum_attempts(): void
    {
        $this->seed(ToolRegistrySeeder::class);
        $user = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-RETRY', 'name' => 'Retry', 'created_by' => $user->id]);
        $case->members()->attach($user->id, ['role_in_case' => 'lead']);
        $evidence = Evidence::query()->create(['case_id' => $case->id, 'evidence_number' => 'EVD-RETRY', 'name' => 'Retry PCAP', 'type' => 'pcap', 'storage_key' => 'evidence/retry/input.pcap', 'size_bytes' => 8, 'status' => 'verified', 'uploaded_by' => $user->id]);
        $tool = ForensicTool::query()->where('slug', 'tshark')->firstOrFail();
        $operation = ToolOperation::query()->where('tool_id', $tool->id)->where('operation_key', 'packet_summary')->firstOrFail();
        $job = AnalysisJob::query()->create(['public_id' => 'a8d21e68-0eca-4eae-a82d-8ea7cfad986a', 'case_id' => $case->id, 'evidence_id' => $evidence->id, 'tool_id' => $tool->id, 'operation_id' => $operation->id, 'status' => 'failed', 'attempt_count' => 1, 'max_attempts' => 2, 'requested_by' => $user->id]);

        $this->actingAs($user)->postJson('/api/v1/analysis-jobs/'.$job->public_id.'/retry')->assertOk()->assertJsonPath('status', 'queued');
    }

    public function test_administrator_can_manage_category_operation_and_version_evidence_while_evidence_type_allowlist_is_enforced(): void
    {
        $this->seed(ToolRegistrySeeder::class);
        $administrator = User::factory()->create(['role' => 'administrator']);
        $investigator = User::factory()->create(['role' => 'investigator']);
        $category = $this->actingAs($administrator)->postJson('/api/v1/tool-categories', ['name' => 'Custom Analysis', 'description' => 'Controlled test category'])->assertCreated()->json();
        $tool = $this->actingAs($administrator)->postJson('/api/v1/tools', ['category_id' => $category['id'], 'name' => 'Metadata Scanner', 'adapter_key' => 'metadata.scanner'])->assertCreated()->json();
        $operation = $this->actingAs($administrator)->postJson('/api/v1/tools/'.$tool['id'].'/operations', ['operation_key' => 'scan', 'evidence_types' => ['file'], 'timeout_seconds' => 60, 'max_output_bytes' => 1048576, 'enabled' => false])->assertCreated()->json();
        $this->assertSame(['file'], $operation['evidence_types_json']);
        $this->actingAs($administrator)->postJson('/api/v1/tools/'.$tool['id'].'/verification', ['verification_state' => 'configured'])->assertOk();
        $this->actingAs($administrator)->postJson('/api/v1/tools/'.$tool['id'].'/verification', ['verification_state' => 'installed', 'version' => '1.2.3', 'path' => '/opt/metadata-scanner'])->assertOk();
        $this->assertDatabaseHas('tool_versions', ['tool_id' => $tool['id'], 'path' => '/opt/metadata-scanner', 'version' => '1.2.3']);
        $this->actingAs($investigator)->postJson('/api/v1/tool-categories', ['name' => 'Not allowed'])->assertForbidden();
    }
}
