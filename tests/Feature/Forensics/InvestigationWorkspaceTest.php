<?php

namespace Tests\Feature\Forensics;

use App\Models\Evidence;
use App\Models\InvestigationCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestigationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_athar_entry_page_and_investigator_sees_only_assigned_case_records(): void
    {
        $this->get('/')->assertOk()->assertSee('ATHAR')->assertSee('سلسلة الحيازة');
        $investigator = User::factory()->create(['role' => 'investigator']);
        $otherInvestigator = User::factory()->create(['role' => 'investigator']);
        $assignedCase = InvestigationCase::query()->create(['case_number' => 'ATH-DASH-001', 'name' => 'Assigned case', 'created_by' => $investigator->id]);
        $assignedCase->members()->attach($investigator->id, ['role_in_case' => 'lead']);
        $otherCase = InvestigationCase::query()->create(['case_number' => 'ATH-DASH-002', 'name' => 'Unassigned case', 'created_by' => $otherInvestigator->id]);
        $otherCase->members()->attach($otherInvestigator->id, ['role_in_case' => 'lead']);
        Evidence::query()->create(['case_id' => $assignedCase->id, 'evidence_number' => 'EVD-DASH-001', 'name' => 'Assigned PCAP', 'type' => 'pcap', 'storage_key' => 'evidence/assigned.pcap', 'size_bytes' => 64, 'status' => 'verified', 'uploaded_by' => $investigator->id]);

        $this->actingAs($investigator)->get('/dashboard')->assertOk()->assertSee('لوحة التحقيق')->assertSee('الأدلة الخاصة');
        $this->actingAs($investigator)->get('/workspace/cases')->assertOk()->assertSee('ATH-DASH-001')->assertDontSee('ATH-DASH-002');
        $this->actingAs($investigator)->get('/workspace/evidence')->assertOk()->assertSee('Assigned PCAP');
    }

    public function test_lead_investigator_sees_the_evidence_upload_form_on_the_workspace(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-FORM-001', 'name' => 'Editable case', 'created_by' => $investigator->id]);
        $case->members()->attach($investigator->id, ['role_in_case' => 'lead']);

        // النموذج لا يظهر إلا إذا عُدّت القضية "قابلة للتحرير" للمستخدم الحالي.
        $this->actingAs($investigator)
            ->get('/workspace/evidence')
            ->assertOk()
            ->assertSee('تسجيل دليل جديد');

        // القضية نفسها يجب أن تظهر كخيار داخل النموذج (أي أنها ضمن editableCases).
        $this->actingAs($investigator)
            ->get('/workspace/evidence')
            ->assertOk()
            ->assertSee('ATH-FORM-001');
    }

    public function test_viewer_membership_does_not_grant_editable_workspace_forms(): void
    {
        $investigator = User::factory()->create(['role' => 'investigator']);
        $case = InvestigationCase::query()->create(['case_number' => 'ATH-FORM-002', 'name' => 'Read only case', 'created_by' => $investigator->id]);
        $case->members()->attach($investigator->id, ['role_in_case' => 'observer']);

        $this->actingAs($investigator)
            ->get('/workspace/evidence')
            ->assertOk()
            ->assertDontSee('تسجيل دليل جديد');
    }
}
