<?php

namespace App\Domain\Forensics\Services;

use App\Models\ForensicTool;
use App\Models\ToolVersion;
use Illuminate\Validation\ValidationException;

class ToolVerificationService
{
    public const STATES = ['cataloged', 'configured', 'installed', 'executable', 'end_to_end_verified'];

    public function record(ForensicTool $tool, string $nextState, array $evidence): ToolVersion
    {
        $currentIndex = array_search($tool->verification_state, self::STATES, true);
        $nextIndex = array_search($nextState, self::STATES, true);
        if ($nextIndex === false || $currentIndex === false || $nextIndex !== $currentIndex + 1) {
            throw ValidationException::withMessages(['verification_state' => 'Tool state must advance by exactly one verified stage.']);
        }
        if (in_array($nextState, ['installed', 'executable', 'end_to_end_verified'], true) && (empty($evidence['version']) || empty($evidence['path']))) {
            throw ValidationException::withMessages(['version' => 'Path and version evidence are required from the installed stage onward.']);
        }

        $version = ToolVersion::query()->create([
            'tool_id' => $tool->getKey(),
            'agent_id' => $evidence['agent_id'] ?? null,
            'version' => $evidence['version'] ?? null,
            'path' => $evidence['path'] ?? null,
            'verified_at' => now(),
            'status' => $nextState,
        ]);
        $tool->update(['verification_state' => $nextState]);

        return $version;
    }
}
