<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForensicReport extends Model
{
    protected $table = 'reports';
    protected $fillable = ['case_id', 'version', 'status', 'generated_by', 'file_key', 'content_hash', 'approved_by', 'approved_at'];
    protected function casts(): array { return ['approved_at' => 'datetime']; }
    public function investigationCase(): BelongsTo { return $this->belongsTo(InvestigationCase::class, 'case_id'); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
