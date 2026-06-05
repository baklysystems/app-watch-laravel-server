<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'api_key_prefix',
        'environment',
        'slug',
        'retention_days',
        'rate_limit',
        'is_active',
        'last_seen_at',
        'metadata',
        'integrations_config',
    ];

    protected $casts = [
        'metadata' => 'array',
        'integrations_config' => 'array',
        'is_active' => 'boolean',
        'retention_days' => 'integer',
        'rate_limit' => 'integer',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(AppException::class);
    }

    public function logEntries(): HasMany
    {
        return $this->hasMany(LogEntry::class);
    }

    public function queueJobs(): HasMany
    {
        return $this->hasMany(QueueJob::class);
    }

    public function databaseQueries(): HasMany
    {
        return $this->hasMany(DatabaseQuery::class);
    }

    public function httpRequests(): HasMany
    {
        return $this->hasMany(HttpRequest::class);
    }

    public function scheduledTasks(): HasMany
    {
        return $this->hasMany(ScheduledTask::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function integrationMetrics(): HasMany
    {
        return $this->hasMany(IntegrationMetric::class);
    }

    public function resolveBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_active', true)->first();
    }

    public function resolveByApiKey(string $apiKey): ?self
    {
        $prefix = substr($apiKey, 0, 8);
        return static::where('api_key_prefix', $prefix)
            ->where('is_active', true)
            ->get()
            ->first(fn ($project) => password_verify($apiKey, $project->api_key));
    }
}