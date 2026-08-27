<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['actor_id', 'action', 'entity_type', 'entity_id', 'request_id', 'ip_address', 'metadata_json'];
    protected function casts(): array { return ['metadata_json' => 'array', 'created_at' => 'datetime']; }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
