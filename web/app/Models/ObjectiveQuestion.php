<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectiveQuestion extends Model
{
    protected $table = 'objective_questions';

    public $timestamps = false;

    protected $fillable = [
        'objective_assessment_id',
        'sort_order',
        'question_text',
        'question_type',
        'options',
        'correct_answer',
        'points',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'float',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ObjectiveAssessment::class, 'objective_assessment_id');
    }
}
