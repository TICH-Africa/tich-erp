<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatScore extends Model
{
    protected $table = 'cat_scores';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'unit_id',
        'semester_id',
        'assessment_type',
        'assessment_name',
        'max_score',
        'score_obtained',
        'percentage_score',
        'weight_in_final',
        'recorded_by',
        'verified_by_hod',
        'recorded_at',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'score_obtained' => 'decimal:2',
        'percentage_score' => 'decimal:2',
        'weight_in_final' => 'decimal:2',
        'verified_by_hod' => 'boolean',
        'recorded_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
