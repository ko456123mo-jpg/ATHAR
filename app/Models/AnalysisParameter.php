<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalysisParameter extends Model
{
    protected $fillable = ['analysis_job_id', 'parameters_json'];
    protected function casts(): array { return ['parameters_json' => 'array']; }
    public function job(): BelongsTo { return $this->belongsTo(AnalysisJob::class, 'analysis_job_id'); }
}
