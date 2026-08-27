<?php

namespace Tests\Feature\Forensics;

use App\Models\InvestigationCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaseAndEvidenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_investigator_can_create_a_case_and_register_private_evidence(): void
    {
        Storage::fake('private');
        $investigator = User::factory()->create(['role' => 'investigator']);

        $caseResponse = $this->actingAs($investigator)->postJson('/api/v1/cases', [
            'name' => 'Benign network review',
            'priority' => 'high',
        ]);
        $caseResponse->assertCreated()->assertJsonPath('name', 'Benign network review');
        $case = InvestigationCase::query()->firstOrFail();

        $evidenceResponse = $this->actingAs($investigator)->post('/api/v1/cases/'.$case->id.'/evidence', [
            'name' => 'Capture A',
            'type' => 'pcap',
            'source' => 'training fixture',
            'evidence' => UploadedFile::fake()->create('capture.pcap', 4, 'application/vnd.tcpdump.pcap'),
        ], ['Accept' => 'application/json']);

        $evidenceResponse->assertCreated()->assertJsonPath('type', 'pcap');
        $this->assertDatabaseCount('evidence_hashes', 1);
        $this->assertDatabaseCount('chain_of_custody', 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evidence.registered']);
    }

    public function test_reviewer_cannot_create_cases_or_register_evidence(): void
    {
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-REVIEW', 'name' => 'Restricted', 'created_by' => $reviewer->id]);
        $case->members()->attach($reviewer->id, ['role_in_case' => 'reviewer']);

        $this->actingAs($reviewer)->postJson('/api/v1/cases', ['name' => 'Denied', 'priority' => 'low'])->assertForbidden();
        $this->actingAs($reviewer)->post('/api/v1/cases/'.$case->id.'/evidence', [
            'name' => 'Denied evidence',
            'type' => 'pcap',
            'evidence' => UploadedFile::fake()->create('denied.pcap', 4),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }
}
