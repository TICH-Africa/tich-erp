<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalDevelopment extends Model
{
    protected $table = 'professional_development';

    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'staff_ids',
        'activity_type',
        'activity_name',
        'organizer',
        'start_date',
        'end_date',
        'hours_or_days',
        'cpd_credits_earned',
        'location',
        'is_external',
        'cost',
        'funded_by',
        'certificate_path',
        'is_completed',
        'appraisal_relevance',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'hours_or_days' => 'decimal:2',
        'cpd_credits_earned' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_external' => 'boolean',
        'is_completed' => 'boolean',
        'staff_ids' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function getAssignedStaffIdsAttribute(): array
    {
        if ($this->staff_ids) {
            return $this->staff_ids;
        }

        if ($this->staff_id) {
            return [$this->staff_id];
        }

        return [];
    }

    public function isAssignedToAll(): bool
    {
        return empty($this->staff_ids) && empty($this->staff_id);
    }

    public function isAssignedToStaff(int $staffId): bool
    {
        if ($this->isAssignedToAll()) {
            return true;
        }

        return in_array($staffId, $this->assigned_staff_ids);
    }
}
