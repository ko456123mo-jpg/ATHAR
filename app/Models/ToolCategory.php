<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    public function tools(): HasMany { return $this->hasMany(ForensicTool::class, 'category_id'); }
}
