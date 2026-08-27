<?php

namespace App\Domain\Forensics\Services;

use App\Models\ChainOfCustody;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Support\Arr;

class ChainOfCustodyService
{
    public function record(Evidence $evidence, ?User $actor, string $eventType, string $action, array $metadata = []): ChainOfCustody
    {
        $previous = $evidence->custodyEvents()->latest('id')->first();
        $occurredAt = now();
        $normalized = Arr::sortRecursive($metadata);
        $payload = implode('|', [
            $previous?->event_hash ?? 'GENESIS',
            $evidence->getKey(),
            $actor?->getKey() ?? 'SYSTEM',
            $eventType,
            $action,
            $occurredAt->toIso8601String(),
            json_encode($normalized, JSON_UNESCAPED_SLASHES),
        ]);

        return $evidence->custodyEvents()->create([
            'actor_id' => $actor?->getKey(),
            'event_type' => $eventType,
            'action' => $action,
            'occurred_at' => $occurredAt,
            'metadata_json' => $normalized,
            'previous_event_hash' => $previous?->event_hash,
            'event_hash' => hash('sha256', $payload),
        ]);
    }
}
