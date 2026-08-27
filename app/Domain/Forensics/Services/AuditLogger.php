<?php

namespace App\Domain\Forensics\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    public function record(?User $actor, string $action, object|string $entity, ?int $entityId = null, array $metadata = []): AuditLog
    {
        $entityType = is_object($entity) ? $entity::class : $entity;
        $resolvedEntityId = is_object($entity) && method_exists($entity, 'getKey') ? $entity->getKey() : $entityId;

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $resolvedEntityId,
            'request_id' => request()->header('X-Request-ID'),
            'ip_address' => request()->ip(),
            'metadata_json' => $metadata,
        ]);
    }
}
