<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEvaluation extends Model
{
    protected $fillable = [
        'window_id',
        'student_id',
        'unit_id',
        'staff_id',
        'rating',
        'responses',
        'comments',
        'submitted_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function window(): BelongsTo
    {
        return $this->belongsTo(CourseEvaluationWindow::class, 'window_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
