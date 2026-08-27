<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestigationCase extends Model
{
    use HasFactory;
    protected $table = 'cases';
    protected $fillable = ['case_number', 'name', 'description', 'status', 'priority', 'created_by', 'closed_at'];
    protected function casts(): array { return ['closed_at' => 'datetime']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function members(): BelongsToMany { return $this->belongsToMany(User::class, 'case_user', 'case_id', 'user_id')->withPivot('role_in_case')->withTimestamps(); }
    public function evidence(): HasMany { return $this->hasMany(Evidence::class, 'case_id'); }
    public function analysisJobs(): HasMany { return $this->hasMany(AnalysisJob::class, 'case_id'); }
    public function findings(): HasMany { return $this->hasMany(Finding::class, 'case_id'); }
    public function reports(): HasMany { return $this->hasMany(ForensicReport::class, 'case_id'); }
}
