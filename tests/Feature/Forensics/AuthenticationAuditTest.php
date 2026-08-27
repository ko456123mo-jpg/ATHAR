<?php

namespace Tests\Feature\Forensics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_are_audited_and_audit_workspace_is_role_protected(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator', 'password' => 'password']);
        $reviewer = User::factory()->create(['role' => 'reviewer']);

        $this->post('/login', ['email' => $investigator->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $investigator->id, 'action' => 'auth.login']);
        $this->post('/logout')->assertRedirect('/');
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $investigator->id, 'action' => 'auth.logout']);
        $this->actingAs($investigator)->get('/workspace/custody')->assertForbidden();
        $this->actingAs($reviewer)->get('/workspace/custody')->assertOk();
    }
}
