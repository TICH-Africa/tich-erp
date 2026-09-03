<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ObjectiveAssessment extends Model
{
    protected $table = 'objective_assessments';

    public $timestamps = false;

    protected $fillable = [
        'unit_allocation_id',
        'unit_id',
        'semester_id',
        'name',
        'assessment_type',
        'max_score',
        'created_by',
        'status',
        'auto_graded_at',
        'created_at',
        'updated_at',
        'time_limit_minutes',
        'available_from',
        'available_until',
        'show_results_immediately',
        'allow_multiple_attempts',
        'max_attempts',
        'student_started_at',
        'student_submitted_at',
        'time_taken_seconds',
    ];

    protected $casts = [
        'max_score' => 'float',
        'auto_graded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'show_results_immediately' => 'boolean',
        'allow_multiple_attempts' => 'boolean',
        'student_started_at' => 'datetime',
        'student_submitted_at' => 'datetime',
        'time_taken_seconds' => 'integer',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(UnitAllocation::class, 'unit_allocation_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ObjectiveQuestion::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ObjectiveSubmission::class);
    }

    public function isAvailable(): bool
    {
        $now = now();

        if ($this->status !== 'ready') {
            return false;
        }

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }
}
