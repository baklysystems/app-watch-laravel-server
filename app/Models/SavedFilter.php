<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id',
        'user_id',
        'name',
        'type',
        'filters',
        'is_default',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_default' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}