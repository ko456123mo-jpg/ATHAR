<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evidence extends Model
{
    use HasFactory;
    protected $table = 'evidence';
    protected $fillable = ['case_id', 'evidence_number', 'name', 'original_filename', 'type', 'storage_disk', 'storage_key', 'size_bytes', 'mime_type', 'source', 'status', 'uploaded_by'];
    protected function casts(): array { return ['size_bytes' => 'integer']; }
    public function investigationCase(): BelongsTo { return $this->belongsTo(InvestigationCase::class, 'case_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function hashes(): HasMany { return $this->hasMany(EvidenceHash::class, 'evidence_id'); }
    public function custodyEvents(): HasMany { return $this->hasMany(ChainOfCustody::class, 'evidence_id'); }
    public function analysisJobs(): HasMany { return $this->hasMany(AnalysisJob::class, 'evidence_id'); }
}
