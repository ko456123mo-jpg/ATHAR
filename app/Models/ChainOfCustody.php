<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChainOfCustody extends Model
{
    protected $table = 'chain_of_custody';
    protected $fillable = ['evidence_id', 'actor_id', 'event_type', 'action', 'occurred_at', 'metadata_json', 'previous_event_hash', 'event_hash'];
    protected function casts(): array { return ['occurred_at' => 'datetime', 'metadata_json' => 'array']; }
    public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
