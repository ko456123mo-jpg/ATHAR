<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ChainOfCustodyService;
use App\Http\Controllers\Controller;
use App\Models\AnalysisResult;
use App\Models\Evidence;
use App\Models\Finding;
use App\Models\InvestigationCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FindingController extends Controller
{
    public function store(Request $request, InvestigationCase $case, AuditLogger $audit, ChainOfCustodyService $custody): JsonResponse
    {
        $this->authorize('update', $case);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:informational,low,medium,high,critical'],
            'description' => ['required', 'string', 'max:20000'],
            'evidence_id' => ['nullable', 'integer'],
            'analysis_result_id' => ['nullable', 'integer'],
        ]);
        $evidence = isset($data['evidence_id']) ? Evidence::query()->whereKey($data['evidence_id'])->where('case_id', $case->getKey())->firstOrFail() : null;
        $result = isset($data['analysis_result_id']) ? AnalysisResult::query()->whereKey($data['analysis_result_id'])->whereHas('job', fn ($jobs) => $jobs->where('case_id', $case->getKey()))->firstOrFail() : null;
        $finding = Finding::query()->create([...$data, 'case_id' => $case->getKey(), 'created_by' => $request->user()->getKey()]);
        if ($evidence) {
            $custody->record($evidence, $request->user(), 'finding_created', 'finding.created', ['finding_id' => $finding->getKey()]);
        }
        $audit->record($request->user(), 'finding.created', $finding);

        return response()->json($finding, 201);
    }

    public function review(Request $request, Finding $finding, AuditLogger $audit, ChainOfCustodyService $custody): JsonResponse
    {
        $this->authorize('view', $finding->investigationCase);
        Gate::authorize('review-findings');
        $data = $request->validate(['status' => ['required', 'in:reviewed,confirmed,rejected']]);
        $finding->update(['status' => $data['status'], 'reviewed_by' => $request->user()->getKey(), 'reviewed_at' => now()]);
        if ($finding->evidence) {
            $custody->record($finding->evidence, $request->user(), 'finding_reviewed', 'finding.'.$data['status'], ['finding_id' => $finding->getKey()]);
        }
        $audit->record($request->user(), 'finding.reviewed', $finding, metadata: ['status' => $data['status']]);

        return response()->json($finding->fresh());
    }
}
