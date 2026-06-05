<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppException extends Model
{
    use HasUuids;

    protected $table = 'exceptions';

    protected $fillable = [
        'project_id',
        'fingerprint',
        'class',
        'message',
        'file',
        'line',
        'code_snippet',
        'stack_trace',
        'request_data',
        'user_data',
        'breadcrumbs',
        'environment',
        'release',
        'severity',
        'status',
        'occurrence_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'code_snippet' => 'array',
        'stack_trace' => 'array',
        'request_data' => 'array',
        'user_data' => 'array',
        'breadcrumbs' => 'array',
        'occurrence_count' => 'integer',
        'line' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function queueJobs(): HasMany
    {
        return $this->hasMany(QueueJob::class, 'exception_id');
    }

    public function scheduledTasks(): HasMany
    {
        return $this->hasMany(ScheduledTask::class, 'exception_id');
    }
}