<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolVersion extends Model
{
    protected $fillable = ['tool_id', 'agent_id', 'version', 'path', 'verified_at', 'status'];
    protected function casts(): array { return ['verified_at' => 'datetime']; }
    public function tool(): BelongsTo { return $this->belongsTo(ForensicTool::class, 'tool_id'); }
    public function agent(): BelongsTo { return $this->belongsTo(ForensicAgent::class, 'agent_id'); }
}
