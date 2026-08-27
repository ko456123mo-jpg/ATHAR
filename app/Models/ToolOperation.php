<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolOperation extends Model
{
    protected $fillable = ['tool_id', 'operation_key', 'input_schema_json', 'output_schema_json', 'evidence_types_json', 'timeout_seconds', 'max_output_bytes', 'enabled'];
    protected function casts(): array { return ['input_schema_json' => 'array', 'output_schema_json' => 'array', 'evidence_types_json' => 'array', 'enabled' => 'boolean']; }
    public function tool(): BelongsTo { return $this->belongsTo(ForensicTool::class, 'tool_id'); }
}
