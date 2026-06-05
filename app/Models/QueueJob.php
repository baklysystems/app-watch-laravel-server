<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueJob extends Model
{
    use HasUuids;

    protected $table = 'queue_jobs';

    protected $fillable = [
        'project_id', 'connection', 'queue', 'job_name', 'payload',
        'attempt', 'max_attempts', 'status', 'exception_id',
        'queued_at', 'started_at', 'finished_at', 'duration_ms',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt' => 'integer',
        'max_attempts' => 'integer',
        'duration_ms' => 'integer',
        'queued_at' => 'datetime',
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