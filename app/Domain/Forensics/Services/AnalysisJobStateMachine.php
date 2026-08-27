<?php

namespace App\Domain\Forensics\Services;

use App\Models\AnalysisJob;
use Illuminate\Validation\ValidationException;

class AnalysisJobStateMachine
{
    private const TRANSITIONS = [
        'created' => ['queued', 'cancelled'],
        'queued' => ['claimed', 'cancelled'],
        'claimed' => ['running', 'failed', 'timed_out', 'cancelled'],
        'running' => ['parsing', 'failed', 'timed_out', 'cancelled'],
        'parsing' => ['completed', 'failed'],
        'completed' => [],
        'failed' => [],
        'timed_out' => [],
        'cancelled' => [],
    ];

    public function transition(AnalysisJob $job, string $nextStatus, array $attributes = []): AnalysisJob
    {
        if (! in_array($nextStatus, self::TRANSITIONS[$job->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => "Invalid transition from {$job->status} to {$nextStatus}."]);
        }

        $timestamps = match ($nextStatus) {
            'queued' => ['queued_at' => now()],
            'claimed' => ['claimed_at' => now()],
            'running' => ['started_at' => now()],
            'completed', 'failed', 'timed_out', 'cancelled' => ['finished_at' => now()],
            default => [],
        };
        $job->update([...$attributes, ...$timestamps, 'status' => $nextStatus]);

        return $job->fresh();
    }
}
