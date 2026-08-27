<?php

namespace Tests\Feature\Forensics;

use App\Models\ForensicAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForensicAgentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_register_rotate_and_retire_an_agent_with_one_time_tokens(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);

        $create = $this->actingAs($administrator)->postJson('/api/v1/agents', ['name' => 'caine-vbox-01', 'hostname' => 'caine-vbox-01.internal']);
        $create->assertCreated()->assertJsonPath('agent.name', 'caine-vbox-01')->assertJsonStructure(['agent' => ['id', 'name', 'status'], 'bootstrap_token']);
        $token = $create->json('bootstrap_token');
        $agent = ForensicAgent::query()->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'agent.registered', 'entity_id' => $agent->id]);
        $this->withToken($token)->postJson('/api/v1/agent/heartbeat', ['hostname' => 'caine-vbox-01.internal'])->assertOk();

        $rotate = $this->actingAs($administrator)->postJson('/api/v1/agents/'.$agent->id.'/rotate-token');
        $rotate->assertOk()->assertJsonPath('agent.status', 'offline')->assertJsonStructure(['bootstrap_token']);
        $this->withToken($token)->postJson('/api/v1/agent/heartbeat')->assertUnauthorized();
        $this->withToken($rotate->json('bootstrap_token'))->postJson('/api/v1/agent/heartbeat')->assertOk();

        $this->actingAs($administrator)->postJson('/api/v1/agents/'.$agent->id.'/retire')->assertOk()->assertJsonPath('status', 'retired');
        $this->withToken($rotate->json('bootstrap_token'))->postJson('/api/v1/agent/heartbeat')->assertUnauthorized();
        $this->assertDatabaseHas('audit_logs', ['action' => 'agent.retired', 'entity_id' => $agent->id]);
    }

    public function test_non_administrator_cannot_manage_forensic_agents(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);

        $this->actingAs($investigator)->getJson('/api/v1/agents')->assertForbidden();
        $this->actingAs($investigator)->postJson('/api/v1/agents', ['name' => 'forbidden-agent'])->assertForbidden();
    }
}
