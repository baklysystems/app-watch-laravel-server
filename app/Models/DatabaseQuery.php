<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseQuery extends Model
{
    use HasUuids;

    protected $table = 'database_queries';

    protected $fillable = [
        'project_id', 'batch_id', 'sql', 'bindings', 'duration_ms',
        'connection_name', 'file', 'line', 'is_slow', 'trace_id', 'occurred_at',
    ];

    protected $casts = [
        'bindings' => 'array',
        'duration_ms' => 'float',
        'is_slow' => 'boolean',
        'line' => 'integer',
        'occurred_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}