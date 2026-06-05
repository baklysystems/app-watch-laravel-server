<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id',
        'key',
        'key_prefix',
        'name',
        'last_used_at',
    ];

    protected $hidden = [
        'key',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}