<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffOnboarding extends Model
{
    protected $table = 'staff_onboarding';

    protected $fillable = [
        'staff_id',
        'applicant_id',
        'onboarding_number',
        'current_step',
        'status',
        'rejection_reason',
        'completed_steps',
        'missing_documents',
        'is_biodata_locked',
        'locked_by',
        'locked_at',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'missing_documents' => 'array',
        'is_biodata_locked' => 'boolean',
        'locked_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'applicant_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'locked_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }
}
