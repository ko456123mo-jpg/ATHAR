<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ChainOfCustodyService;
use App\Domain\Forensics\Services\AnalysisJobStateMachine;
use App\Http\Controllers\Controller;
use App\Jobs\PublishAnalysisJob;
use App\Models\AnalysisJob;
use App\Models\Evidence;
use App\Models\ForensicTool;
use App\Models\InvestigationCase;
use App\Models\ToolOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnalysisJobController extends Controller
{
    public function store(Request $request, InvestigationCase $case, AuditLogger $audit, ChainOfCustodyService $custody): JsonResponse
    {
        $this->authorize('update', $case);
        $data = $request->validate([
            'evidence_id' => ['required', 'integer'],
            'tool_id' => ['required', 'integer'],
            'operation_id' => ['required', 'integer'],
            'parameters' => ['nullable', 'array'],
        ]);

        $evidence = Evidence::query()->whereKey($data['evidence_id'])->where('case_id', $case->getKey())->firstOrFail();
        $tool = ForensicTool::query()->whereKey($data['tool_id'])->where('enabled', true)->firstOrFail();
        $operation = ToolOperation::query()->whereKey($data['operation_id'])->where('tool_id', $tool->getKey())->where('enabled', true)->firstOrFail();
        if (! $evidence->working_copy_key || ! Storage::disk(config('forensics.evidence_disk'))->exists($evidence->working_copy_key)) {
            return response()->json(['message' => 'A verified working copy is required before analysis can be queued.'], 422);
        }
        if (! in_array($evidence->type, $operation->evidence_types_json ?? [], true)) {
            return response()->json(['message' => 'Evidence type is not allowed for this operation.'], 422);
        }

        $job = DB::transaction(function () use ($request, $case, $evidence, $tool, $operation, $data, $audit, $custody): AnalysisJob {
            $job = AnalysisJob::query()->create([
                'public_id' => (string) Str::uuid(),
                'case_id' => $case->getKey(),
                'evidence_id' => $evidence->getKey(),
                'tool_id' => $tool->getKey(),
                'operation_id' => $operation->getKey(),
                'status' => 'created',
                'requested_by' => $request->user()->getKey(),
            ]);
            $job->parameters()->create(['parameters_json' => $data['parameters'] ?? []]);
            $custody->record($evidence, $request->user(), 'analysis_requested', 'analysis.job_created', ['analysis_job_id' => $job->public_id, 'tool' => $tool->slug, 'operation' => $operation->operation_key]);
            $audit->record($request->user(), 'analysis.job_created', $job, metadata: ['case_id' => $case->getKey(), 'tool_id' => $tool->getKey()]);

            return $job;
        });

        PublishAnalysisJob::dispatch($job->getKey());

        return response()->json($job->fresh(['parameters']), 202);
    }

    public function show(AnalysisJob $job): JsonResponse
    {
        $this->authorize('view', $job->investigationCase);

        return response()->json($job->load(['tool', 'operation', 'agent', 'results']));
    }

    public function cancel(Request $request, AnalysisJob $job, AnalysisJobStateMachine $states, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $job->investigationCase);
        $job = $states->transition($job, 'cancelled');
        $audit->record($request->user(), 'analysis.job_cancelled', $job);

        return response()->json($job);
    }

    public function retry(Request $request, AnalysisJob $job, AnalysisJobStateMachine $states, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $job->investigationCase);
        $job = $states->retry($job);
        $audit->record($request->user(), 'analysis.job_retried', $job);

        return response()->json($job);
    }
}
