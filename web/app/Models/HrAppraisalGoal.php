<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrAppraisalGoal extends Model
{
    protected $table = 'hr_appraisal_goals';

    protected $fillable = [
        'appraisal_id',
        'sort_order',
        'goal_type',
        'corporate_goal_id',
        'title',
        'description',
        'smart_specific',
        'smart_measurable',
        'smart_achievable',
        'smart_relevant',
        'smart_timebound',
        'target_date',
        'weight',
        'employee_achievement',
        'self_rating',
        'manager_rating',
        'manager_comments',
        'status',
    ];

    protected $casts = [
        'target_date' => 'date',
        'weight' => 'decimal:2',
        'self_rating' => 'integer',
        'manager_rating' => 'integer',
        'sort_order' => 'integer',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(HrAppraisal::class, 'appraisal_id');
    }

    public function corporateGoal(): BelongsTo
    {
        return $this->belongsTo(HrCorporateGoal::class, 'corporate_goal_id');
    }

    public function isJdDuties(): bool
    {
        return $this->goal_type === 'jd_duties';
    }
}
