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
    ];

    protected $casts = [
        'max_score' => 'float',
        'auto_graded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
}
