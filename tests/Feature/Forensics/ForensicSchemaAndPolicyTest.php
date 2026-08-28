<?php

namespace Tests\Feature\Forensics;

use App\Domain\Forensics\Services\ChainOfCustodyService;
use App\Models\Evidence;
use App\Models\ForensicAgent;
use App\Models\ForensicTool;
use App\Models\InvestigationCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ForensicSchemaAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_forensic_schema_creates_core_traceability_tables(): void
    {
        foreach (['cases', 'evidence', 'evidence_hashes', 'chain_of_custody', 'tools', 'agents', 'analysis_jobs', 'analysis_results', 'findings', 'timeline_events', 'reports', 'audit_logs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_private_evidence_disk_is_the_default_and_isolated_from_general_local_storage(): void
    {
        $this->assertSame('private', config('filesystems.default'));
        $this->assertNotSame(Storage::disk('private')->path(''), Storage::disk('local')->path(''));
    }

    public function test_chain_of_custody_events_link_to_the_previous_hash(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-001', 'name' => 'Integrity test', 'created_by' => $investigator->id]);
        $evidence = Evidence::query()->create([
            'case_id' => $case->id,
            'evidence_number' => 'EVD-001',
            'name' => 'Benign PCAP',
            'type' => 'pcap',
            'storage_key' => 'private/evidence/EVD-001/input.pcap',
            'size_bytes' => 8,
            'status' => 'registered',
            'uploaded_by' => $investigator->id,
        ]);

        $service = app(ChainOfCustodyService::class);
        $first = $service->record($evidence, $investigator, 'registered', 'evidence.registered');
        $second = $service->record($evidence, $investigator, 'verified', 'evidence.sha256_verified');

        $this->assertNull($first->previous_event_hash);
        $this->assertSame($first->event_hash, $second->previous_event_hash);
        $this->assertNotSame($first->event_hash, $second->event_hash);
    }

    public function test_administrator_role_has_tool_management_permission(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->assertTrue($administrator->can('manage-tools'));
        $this->assertFalse($investigator->can('manage-tools'));
        $this->assertTrue($administrator->can('create', ForensicTool::class));
        $this->assertTrue($administrator->can('create', ForensicAgent::class));
        $this->assertFalse($investigator->can('create', ForensicTool::class));
        $this->assertFalse($investigator->can('create', ForensicAgent::class));
    }
}
