<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrAppraisalCompetency extends Model
{
    protected $table = 'hr_appraisal_competencies';

    protected $fillable = [
        'appraisal_id',
        'category',
        'competency_key',
        'competency_label',
        'is_applicable',
        'self_rating',
        'manager_rating',
        'comments',
    ];

    protected $casts = [
        'is_applicable' => 'boolean',
        'self_rating' => 'integer',
        'manager_rating' => 'integer',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(HrAppraisal::class, 'appraisal_id');
    }
}
