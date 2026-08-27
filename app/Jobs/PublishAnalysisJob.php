<?php

namespace App\Jobs;

use App\Domain\Forensics\Services\AnalysisJobStateMachine;
use App\Models\AnalysisJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishAnalysisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $analysisJobId)
    {
    }

    public function handle(AnalysisJobStateMachine $states): void
    {
        $job = AnalysisJob::query()->findOrFail($this->analysisJobId);
        if ($job->status === 'created') {
            $states->transition($job, 'queued');
        }
    }
}
