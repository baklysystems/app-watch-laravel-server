<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTask extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id', 'command', 'description', 'expression',
        'status', 'exception_id', 'output', 'duration_ms',
        'started_at', 'finished_at',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function exception(): BelongsTo
    {
        return $this->belongsTo(AppException::class, 'exception_id');
    }
}