<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use App\Models\AuditLog;
use App\Models\Evidence;
use App\Models\Finding;
use App\Models\ForensicReport;
use App\Models\ForensicTool;
use App\Models\InvestigationCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ForensicsWorkspaceController extends Controller
{
    public function __invoke(Request $request, string $section)
    {
        $user = $request->user();
        if ($section === 'custody') {
            Gate::authorize('view-audit-log');
        }

        $caseScope = InvestigationCase::query()
            ->when(! $user->isAdministrator(), fn ($query) => $query->whereHas('members', fn ($members) => $members->whereKey($user->getKey())));
        $caseIds = (clone $caseScope)->select('id');
        $editableCaseScope = InvestigationCase::query()
            ->when(! $user->isAdministrator(), fn ($query) => $query->whereHas('members', fn ($members) => $members->whereKey($user->getKey())->wherePivotIn('role_in_case', ['lead', 'member'])));
        $editableCaseIds = (clone $editableCaseScope)->select('id');

        $data = match ($section) {
            'cases' => (clone $caseScope)->with(['creator', 'members'])->latest()->paginate(20),
            'evidence' => Evidence::query()->whereIn('case_id', $caseIds)->with(['investigationCase', 'hashes'])->latest()->paginate(20),
            'jobs' => AnalysisJob::query()->whereIn('case_id', $caseIds)->with(['investigationCase', 'tool', 'agent'])->latest()->paginate(20),
            'tools' => ForensicTool::query()->with(['category', 'operations', 'versions'])->orderBy('name')->paginate(20),
            'findings' => Finding::query()->whereIn('case_id', $caseIds)->with(['investigationCase', 'evidence'])->latest()->paginate(20),
            'reports' => ForensicReport::query()->whereIn('case_id', $caseIds)->with('investigationCase')->latest()->paginate(20),
            'custody' => AuditLog::query()->latest('created_at')->paginate(30),
        };

        return view('workspace.index', [
            'section' => $section,
            'data' => $data,
            'workspace' => [
                'canCreateCase' => $user->can('create', InvestigationCase::class),
                'editableCases' => $editableCaseScope->orderBy('case_number')->get(['id', 'case_number', 'name']),
                'jobEvidence' => Evidence::query()->whereIn('case_id', $editableCaseIds)->whereNotNull('working_copy_key')->orderBy('evidence_number')->get(['id', 'case_id', 'evidence_number', 'name', 'type']),
                'tools' => ForensicTool::query()->where('enabled', true)->with(['operations' => fn ($operations) => $operations->where('enabled', true)])->orderBy('name')->get(),
            ],
        ]);
    }
}
