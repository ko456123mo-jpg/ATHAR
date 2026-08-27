<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceHash extends Model
{
    protected $fillable = ['evidence_id', 'algorithm', 'value', 'calculated_at', 'calculated_by'];
    protected function casts(): array { return ['calculated_at' => 'datetime']; }
    public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
    public function calculator(): BelongsTo { return $this->belongsTo(User::class, 'calculated_by'); }
}
