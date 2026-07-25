<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlanApproval extends Model
{
    protected $table = 'lesson_plan_approvals';

    public $timestamps = false;

    protected $fillable = [
        'lesson_plan_id',
        'approver_id',
        'approval_level',
        'decision',
        'comments',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function lessonPlan(): BelongsTo
    {
        return $this->belongsTo(LessonPlan::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approver_id');
    }
}
