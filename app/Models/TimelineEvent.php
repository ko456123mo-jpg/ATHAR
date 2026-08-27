<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    protected $fillable = ['case_id', 'evidence_id', 'source_result_id', 'event_type', 'event_time', 'timezone', 'description', 'attributes_json'];
    protected function casts(): array { return ['event_time' => 'datetime', 'attributes_json' => 'array']; }
    public function investigationCase(): BelongsTo { return $this->belongsTo(InvestigationCase::class, 'case_id'); }
    public function evidence(): BelongsTo { return $this->belongsTo(Evidence::class); }
    public function sourceResult(): BelongsTo { return $this->belongsTo(AnalysisResult::class, 'source_result_id'); }
}
