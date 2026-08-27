<?php

namespace Tests\Unit;

use App\Domain\Forensics\Services\AnalysisJobStateMachine;
use App\Models\AnalysisJob;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AnalysisJobStateMachineTest extends TestCase
{
    public function test_it_rejects_invalid_state_transitions(): void
    {
        $job = new AnalysisJob(['status' => 'created']);

        $this->expectException(ValidationException::class);
        app(AnalysisJobStateMachine::class)->transition($job, 'completed');
    }
}
