<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationMetric extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id', 'integration', 'metric_name', 'metric_value',
        'unit', 'dimensions', 'recorded_at',
    ];

    protected $casts = [
        'metric_value' => 'float',
        'dimensions' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}