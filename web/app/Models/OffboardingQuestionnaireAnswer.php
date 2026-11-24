<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffboardingQuestionnaireAnswer extends Model
{
    protected $fillable = [
        'invitation_id',
        'question_id',
        'answer_text',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(OffboardingQuestionnaireInvitation::class, 'invitation_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(OffboardingQuestionnaireQuestion::class, 'question_id');
    }
}