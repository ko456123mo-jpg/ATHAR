<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisResult extends Model
{
    protected $fillable = ['analysis_job_id', 'raw_storage_key', 'parsed_json', 'parser_version', 'tool_version', 'adapter_version', 'result_hash'];
    protected function casts(): array { return ['parsed_json' => 'array']; }
    public function job(): BelongsTo { return $this->belongsTo(AnalysisJob::class, 'analysis_job_id'); }
    public function findings(): HasMany { return $this->hasMany(Finding::class, 'analysis_result_id'); }
}
