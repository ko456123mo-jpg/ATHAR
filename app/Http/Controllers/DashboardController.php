<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use App\Models\Evidence;
use App\Models\Finding;
use App\Models\ForensicTool;
use App\Models\InvestigationCase;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $caseScope = InvestigationCase::query();
        if (! $user->isAdministrator()) {
            $caseScope->whereHas('members', fn ($members) => $members->whereKey($user->getKey()));
        }
        $caseIds = (clone $caseScope)->select('id');
        $recentJobs = AnalysisJob::query()->with(['investigationCase', 'tool'])->whereIn('case_id', $caseIds)->latest()->limit(6)->get();

        return view('dashboard', [
            'summary' => [
                'cases' => (clone $caseScope)->count(),
                'evidence' => Evidence::query()->whereIn('case_id', $caseIds)->count(),
                'activeJobs' => AnalysisJob::query()->whereIn('case_id', $caseIds)->whereIn('status', ['created', 'queued', 'claimed', 'running', 'parsing'])->count(),
                'reviewFindings' => Finding::query()->whereIn('case_id', $caseIds)->where('status', 'draft')->count(),
                'verifiedTools' => ForensicTool::query()->where('verification_state', 'end_to_end_verified')->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }
}
