<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForensicTool extends Model
{
    protected $table = 'tools';
    protected $fillable = ['category_id', 'name', 'slug', 'description', 'adapter_key', 'verification_state', 'enabled'];
    protected function casts(): array { return ['enabled' => 'boolean']; }
    public function category(): BelongsTo { return $this->belongsTo(ToolCategory::class, 'category_id'); }
    public function operations(): HasMany { return $this->hasMany(ToolOperation::class, 'tool_id'); }
    public function versions(): HasMany { return $this->hasMany(ToolVersion::class, 'tool_id'); }
}
