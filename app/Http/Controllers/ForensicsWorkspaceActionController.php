<?php

namespace App\Http\Controllers;

use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ChainOfCustodyService;
use App\Domain\Forensics\Services\ReportService;
use App\Domain\Forensics\Services\AnalysisJobStateMachine;
use App\Http\Controllers\Api\AnalysisJobController;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\FindingController;
use App\Http\Controllers\Api\ReportController;
use App\Models\AnalysisJob;
use App\Models\Evidence;
use App\Models\Finding;
use App\Models\ForensicReport;
use App\Models\InvestigationCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForensicsWorkspaceActionController extends Controller
{
    public function storeCase(Request $request, CaseController $cases, AuditLogger $audit): RedirectResponse
    {
        $cases->store($request, $audit);

        return $this->complete('cases', 'تم إنشاء القضية وإسنادك قائدًا لها.');
    }

    public function storeEvidence(Request $request, EvidenceController $evidence, \App\Domain\Forensics\Services\EvidenceService $service): RedirectResponse
    {
        $case = InvestigationCase::query()->findOrFail($request->integer('case_id'));
        $evidence->store($request, $case, $service);

        return $this->complete('evidence', 'تم حفظ الدليل في التخزين الخاص وحساب بصمة SHA-256.');
    }

    public function verifyEvidence(Request $request, Evidence $evidence, EvidenceController $controller, \App\Domain\Forensics\Services\EvidenceService $service): RedirectResponse
    {
        $controller->verify($request, $evidence, $service);

        return $this->complete('evidence', 'اكتمل التحقق من سلامة الدليل وسُجل الحدث.');
    }

    public function createWorkingCopy(Request $request, Evidence $evidence, EvidenceController $controller, \App\Domain\Forensics\Services\EvidenceService $service): RedirectResponse
    {
        $controller->createWorkingCopy($request, $evidence, $service);

        return $this->complete('evidence', 'تم إنشاء نسخة عمل للتحليل؛ الأصل لم يُعدّل.');
    }

    public function storeJob(Request $request, AnalysisJobController $jobs, AuditLogger $audit, ChainOfCustodyService $custody): RedirectResponse
    {
        $case = InvestigationCase::query()->findOrFail($request->integer('case_id'));
        $jobs->store($request, $case, $audit, $custody);

        return $this->complete('jobs', 'أُنشئت مهمة التحليل وأُرسلت إلى قائمة الانتظار.');
    }

    public function cancelJob(Request $request, AnalysisJob $job, AnalysisJobController $jobs, AnalysisJobStateMachine $states, AuditLogger $audit): RedirectResponse
    {
        $jobs->cancel($request, $job, $states, $audit);

        return $this->complete('jobs', 'تم إلغاء المهمة وتسجيل قرار الإلغاء.');
    }

    public function retryJob(Request $request, AnalysisJob $job, AnalysisJobController $jobs, AnalysisJobStateMachine $states, AuditLogger $audit): RedirectResponse
    {
        $jobs->retry($request, $job, $states, $audit);

        return $this->complete('jobs', 'أُعيدت المهمة المؤهلة إلى قائمة الانتظار.');
    }

    public function storeFinding(Request $request, FindingController $findings, AuditLogger $audit, ChainOfCustodyService $custody): RedirectResponse
    {
        $case = InvestigationCase::query()->findOrFail($request->integer('case_id'));
        $findings->store($request, $case, $audit, $custody);

        return $this->complete('findings', 'تم إنشاء النتيجة بصفتها مسودة قابلة للمراجعة.');
    }

    public function reviewFinding(Request $request, Finding $finding, FindingController $findings, AuditLogger $audit, ChainOfCustodyService $custody): RedirectResponse
    {
        $findings->review($request, $finding, $audit, $custody);

        return $this->complete('findings', 'تم حفظ قرار المراجعة وتسجيله.');
    }

    public function generateReport(Request $request, ReportController $reports, ReportService $service): RedirectResponse
    {
        $case = InvestigationCase::query()->findOrFail($request->integer('case_id'));
        $reports->store($request, $case, $service);

        return $this->complete('reports', 'تم إنشاء إصدار تقرير خاص مع بصمة محتوى.');
    }

    public function approveReport(Request $request, ForensicReport $report, ReportController $reports, AuditLogger $audit, ChainOfCustodyService $custody): RedirectResponse
    {
        $reports->approve($request, $report, $audit, $custody);

        return $this->complete('reports', 'تمت الموافقة على التقرير وتسجيلها في سلسلة الحيازة.');
    }

    private function complete(string $section, string $message): RedirectResponse
    {
        return to_route('workspace', ['section' => $section])->with('status', $message);
    }
}
