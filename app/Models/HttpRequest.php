<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HttpRequest extends Model
{
    use HasUuids;

    protected $table = 'http_requests';

    protected $fillable = [
        'project_id', 'trace_id', 'method', 'url', 'route_name',
        'controller_action', 'status_code', 'duration_ms', 'memory_usage_mb',
        'request_headers', 'request_body', 'response_headers', 'response_body',
        'ip_address', 'user_agent', 'user_id', 'occurred_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'status_code' => 'integer',
        'duration_ms' => 'float',
        'memory_usage_mb' => 'float',
        'occurred_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}