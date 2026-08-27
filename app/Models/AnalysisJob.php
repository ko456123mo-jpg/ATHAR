<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisJob extends Model
{
    public const TERMINAL_STATUSES = ['completed', 'failed', 'timed_out', 'cancelled'];
    protected $fillable = ['public_id', 'case_id', 'evidence_id', 'tool_id', 'operation_id', 'agent_id', 'status', 'attempt_count', 'max_attempts', 'requested_by', 'queued_at', 'claimed_at', 'started_at', 'finished_at', 'exit_code', 'error_message'];
    protected function casts(): array { return ['queued_at' => 'datetime', 'claimed_at' => 'datetime', 'started_at' => 'datetime', 'finished_at' => 'datetime']; }
    public function investigationCase(): BelongsTo { return $this->belongsTo(InvestigationCase::class, 'case_id'); }
    public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
    public function tool(): BelongsTo { return $this->belongsTo(ForensicTool::class, 'tool_id'); }
    public function operation(): BelongsTo { return $this->belongsTo(ToolOperation::class, 'operation_id'); }
    public function agent(): BelongsTo { return $this->belongsTo(ForensicAgent::class, 'agent_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function parameters(): HasMany { return $this->hasMany(AnalysisParameter::class); }
    public function results(): HasMany { return $this->hasMany(AnalysisResult::class); }
}
