<?php

namespace App\Console\Commands;

use App\Domain\Forensics\Services\AnalysisJobStateMachine;
use App\Domain\Forensics\Services\AuditLogger;
use App\Domain\Forensics\Services\ChainOfCustodyService;
use App\Models\AnalysisJob;
use Illuminate\Console\Command;

class ExpireAnalysisJobs extends Command
{
    protected $signature = 'athar:expire-analysis-jobs';
    protected $description = 'Mark overdue claimed or running analysis jobs as timed out.';

    public function handle(AnalysisJobStateMachine $states, AuditLogger $audit, ChainOfCustodyService $custody): int
    {
        $expired = 0;
        AnalysisJob::query()->with(['operation', 'evidence'])->whereIn('status', ['claimed', 'running'])->each(function (AnalysisJob $job) use ($states, $audit, $custody, &$expired): void {
            $startedAt = $job->started_at ?? $job->claimed_at;
            if (! $startedAt || $startedAt->addSeconds($job->operation->timeout_seconds)->isFuture()) {
                return;
            }
            $timedOut = $states->transition($job, 'timed_out', ['error_message' => 'Platform execution timeout exceeded.']);
            $custody->record($timedOut->evidence, null, 'analysis_timed_out', 'analysis.timed_out', ['analysis_job_id' => $timedOut->public_id]);
            $audit->record(null, 'analysis.job_timed_out', $timedOut);
            $expired++;
        });

        $this->info("Expired {$expired} analysis job(s).");
        return self::SUCCESS;
    }
}
