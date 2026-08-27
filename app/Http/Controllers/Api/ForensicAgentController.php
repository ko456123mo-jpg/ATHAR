<?php

namespace App\Http\Controllers\Api;

use App\Domain\Forensics\Services\AnalysisJobStateMachine;
use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ChainOfCustodyService;
use App\Http\Controllers\Controller;
use App\Models\AnalysisJob;
use App\Models\AnalysisResult;
use App\Models\ForensicAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ForensicAgentController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate(['hostname' => ['nullable', 'string', 'max:255'], 'capabilities' => ['nullable', 'array']]);
        $agent = $this->agent($request);
        $agent->update(['hostname' => $data['hostname'] ?? $agent->hostname, 'capabilities_json' => $data['capabilities'] ?? $agent->capabilities_json, 'status' => 'online', 'last_seen_at' => now()]);

        return response()->json(['agent_id' => $agent->getKey(), 'status' => 'online']);
    }

    public function claim(Request $request, AnalysisJobStateMachine $states, AuditLogger $audit): JsonResponse
    {
        $agent = $this->agent($request);
        $job = DB::transaction(function () use ($agent, $states, $audit): ?AnalysisJob {
            $job = AnalysisJob::query()->where('status', 'queued')->whereNull('agent_id')
                ->whereHas('tool', fn ($query) => $query->where('enabled', true))
                ->whereHas('operation', fn ($query) => $query->where('enabled', true))
                ->whereHas('evidence', fn ($query) => $query->whereNotNull('working_copy_key'))
                ->lockForUpdate()->first();
            if (! $job) {
                return null;
            }

            $claimed = $states->transition($job, 'claimed', ['agent_id' => $agent->getKey(), 'attempt_count' => $job->attempt_count + 1]);
            $audit->record(null, 'agent.job_claimed', $claimed, metadata: ['agent_id' => $agent->getKey()]);

            return $claimed;
        });
        if (! $job) {
            return response()->json(['job' => null]);
        }

        $job->load(['tool', 'operation', 'evidence', 'parameters']);
        return response()->json([
            'job_id' => $job->public_id,
            'tool_id' => $job->tool->slug,
            'operation_key' => $job->operation->operation_key,
            'evidence_reference' => $job->evidence->working_copy_key,
            'parameters' => $job->parameters->first()?->parameters_json ?? [],
            'limits' => ['timeout_seconds' => $job->operation->timeout_seconds, 'max_output_bytes' => $job->operation->max_output_bytes],
        ]);
    }

    public function status(Request $request, AnalysisJob $job, AnalysisJobStateMachine $states, AuditLogger $audit, ChainOfCustodyService $custody): JsonResponse
    {
        $agent = $this->agent($request);
        abort_unless($job->agent_id === $agent->getKey(), 403, 'Job is not assigned to this agent.');
        $data = $request->validate(['status' => ['required', 'in:running,failed,timed_out'], 'exit_code' => ['nullable', 'integer'], 'error_message' => ['nullable', 'string', 'max:10000']]);
        $job = $states->transition($job, $data['status'], ['exit_code' => $data['exit_code'] ?? null, 'error_message' => $data['error_message'] ?? null]);
        $custody->record($job->evidence, null, 'analysis_status', 'analysis.'.$data['status'], ['analysis_job_id' => $job->public_id, 'agent_id' => $agent->getKey()]);
        $audit->record(null, 'agent.job_status', $job, metadata: ['agent_id' => $agent->getKey(), 'status' => $data['status']]);

        return response()->json($job);
    }

    public function result(Request $request, AnalysisJob $job, AnalysisJobStateMachine $states, AuditLogger $audit, ChainOfCustodyService $custody): JsonResponse
    {
        $agent = $this->agent($request);
        abort_unless($job->agent_id === $agent->getKey(), 403, 'Job is not assigned to this agent.');
        $data = $request->validate([
            'raw_output' => ['required', 'string'],
            'parsed_result' => ['required', 'array'],
            'tool_version' => ['required', 'string', 'max:128'],
            'adapter_version' => ['required', 'string', 'max:64'],
            'exit_code' => ['required', 'integer'],
        ]);
        abort_if(strlen($data['raw_output']) > $job->operation->max_output_bytes, 422, 'Raw output exceeds configured limit.');

        $result = DB::transaction(function () use ($data, $job, $states, $agent, $audit, $custody): AnalysisResult {
            $job = $states->transition($job, 'parsing', ['exit_code' => $data['exit_code']]);
            $rawKey = "results/{$job->public_id}/raw-output.txt";
            Storage::disk(config('forensics.evidence_disk'))->put($rawKey, $data['raw_output']);
            $result = AnalysisResult::query()->create([
                'analysis_job_id' => $job->getKey(),
                'raw_storage_key' => $rawKey,
                'parsed_json' => $data['parsed_result'],
                'parser_version' => 'tshark-csv-v1',
                'tool_version' => $data['tool_version'],
                'adapter_version' => $data['adapter_version'],
                'result_hash' => hash('sha256', json_encode($data['parsed_result'], JSON_UNESCAPED_SLASHES)),
            ]);
            $job = $states->transition($job, 'completed');
            $custody->record($job->evidence, null, 'analysis_result_created', 'analysis.result_created', ['analysis_job_id' => $job->public_id, 'analysis_result_id' => $result->getKey(), 'agent_id' => $agent->getKey()]);
            $audit->record(null, 'agent.result_submitted', $result, metadata: ['agent_id' => $agent->getKey(), 'analysis_job_id' => $job->public_id]);

            return $result;
        });

        return response()->json($result, 201);
    }

    private function agent(Request $request): ForensicAgent
    {
        return $request->attributes->get('forensicAgent');
    }
}
