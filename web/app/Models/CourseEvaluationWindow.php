<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEvaluationWindow extends Model
{
    protected $fillable = [
        'title',
        'semester_id',
        'opens_at',
        'closes_at',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function evaluations(): HasMany
    {
        return $this->hasMany(CourseEvaluation::class, 'window_id');
    }

    public function isOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return $this->opens_at <= $now && $this->closes_at >= $now;
    }
}
