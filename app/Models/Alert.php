<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id', 'name', 'type', 'conditions', 'channels',
        'cooldown_minutes', 'is_active', 'last_triggered_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'channels' => 'array',
        'cooldown_minutes' => 'integer',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}