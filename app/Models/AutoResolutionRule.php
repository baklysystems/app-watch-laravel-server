<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AutoResolutionRule extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'project_id', 'name', 'rule_type', 'conditions',
        'is_active', 'last_evaluated_at', 'execution_count',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'last_evaluated_at' => 'datetime',
        'execution_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}