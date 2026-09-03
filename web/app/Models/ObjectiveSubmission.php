<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectiveSubmission extends Model
{
    protected $table = 'objective_submissions';

    public $timestamps = false;

    protected $fillable = [
        'objective_assessment_id',
        'student_id',
        'responses',
        'score_obtained',
        'percentage_score',
        'correct_count',
        'question_count',
        'auto_graded_at',
        'created_at',
        'updated_at',
        'student_started_at',
        'student_submitted_at',
        'time_taken_seconds',
        'attempt_number',
    ];

    protected $casts = [
        'responses' => 'array',
        'score_obtained' => 'float',
        'percentage_score' => 'float',
        'auto_graded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'student_started_at' => 'datetime',
        'student_submitted_at' => 'datetime',
        'time_taken_seconds' => 'integer',
        'attempt_number' => 'integer',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ObjectiveAssessment::class, 'objective_assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
