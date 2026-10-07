<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrAppraisal extends Model
{
    protected $table = 'hr_appraisals';

    protected $fillable = [
        'appraisal_number',
        'cycle_id',
        'staff_id',
        'line_manager_id',
        'department_id',
        'job_title_snapshot',
        'job_description_snapshot',
        'status',
        'objectives_score',
        'competencies_score',
        'overall_score',
        'calibrated_score',
        'overall_rating',
        'strengths',
        'development_areas',
        'training_recommendations',
        'employee_self_comments',
        'manager_objectives_comments',
        'manager_competencies_comments',
        'hr_comments',
        'calibration_reason',
        'staff_agrees',
        'goals_submitted_at',
        'goals_approved_at',
        'goals_approved_by',
        'self_submitted_at',
        'manager_submitted_at',
        'manager_reviewed_by',
        'calibrated_at',
        'calibrated_by',
        'hr_signed_at',
        'hr_signed_by',
        'completed_at',
    ];

    protected $casts = [
        'objectives_score' => 'decimal:2',
        'competencies_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'calibrated_score' => 'decimal:2',
        'staff_agrees' => 'boolean',
        'goals_submitted_at' => 'datetime',
        'goals_approved_at' => 'datetime',
        'self_submitted_at' => 'datetime',
        'manager_submitted_at' => 'datetime',
        'calibrated_at' => 'datetime',
        'hr_signed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(HrAppraisalCycle::class, 'cycle_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'line_manager_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(HrAppraisalGoal::class, 'appraisal_id')->orderBy('sort_order');
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(HrAppraisalCompetency::class, 'appraisal_id');
    }

    public function goalsApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'goals_approved_by');
    }

    public function managerReviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_reviewed_by');
    }

    public function calibratedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'calibrated_by');
    }

    public function hrSignedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hr_signed_by');
    }

    public function statusLabel(): string
    {
        return config('tich-performance-appraisals.statuses.'.$this->status, $this->status);
    }

    public function finalScore(): ?float
    {
        if ($this->calibrated_score !== null) {
            return (float) $this->calibrated_score;
        }

        return $this->overall_score !== null ? (float) $this->overall_score : null;
    }

    public function canEmployeeEditGoals(): bool
    {
        return in_array($this->status, ['draft_goals', 'goals_pending_approval'], true);
    }

    public function canEmployeeSelfAssess(): bool
    {
        return $this->status === 'self_assessment';
    }

    public function canManagerReview(): bool
    {
        return $this->status === 'manager_review';
    }

    public function canManagerApproveGoals(): bool
    {
        return $this->status === 'goals_pending_approval';
    }
}
