<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForensicAgent extends Model
{
    protected $table = 'agents';
    protected $hidden = ['token_hash'];
    protected $fillable = ['name', 'token_hash', 'hostname', 'status', 'last_seen_at', 'capabilities_json'];
    protected function casts(): array { return ['last_seen_at' => 'datetime', 'capabilities_json' => 'array']; }
    public function jobs(): HasMany { return $this->hasMany(AnalysisJob::class, 'agent_id'); }
}
