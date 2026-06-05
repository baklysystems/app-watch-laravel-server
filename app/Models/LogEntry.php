<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id', 'batch_id', 'level', 'message', 'context',
        'channel', 'file', 'line', 'trace_id', 'occurred_at',
    ];

    protected $casts = [
        'context' => 'array',
        'line' => 'integer',
        'occurred_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}