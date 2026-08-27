<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finding extends Model
{
    protected $fillable = ['case_id', 'evidence_id', 'analysis_result_id', 'title', 'severity', 'description', 'status', 'created_by', 'reviewed_by', 'reviewed_at'];
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
    public function investigationCase(): BelongsTo { return $this->belongsTo(InvestigationCase::class, 'case_id'); }
    public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
    public function analysisResult(): BelongsTo { return $this->belongsTo(AnalysisResult::class, 'analysis_result_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
