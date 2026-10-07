<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrAppraisalCycle extends Model
{
    protected $table = 'hr_appraisal_cycles';

    protected $fillable = [
        'name',
        'fiscal_year',
        'quarter',
        'period_start',
        'period_end',
        'status',
        'initiated_by',
        'instructions',
        'calibration_notes',
        'opened_at',
        'calibration_started_at',
        'closed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opened_at' => 'datetime',
        'calibration_started_at' => 'datetime',
        'closed_at' => 'datetime',
        'fiscal_year' => 'integer',
        'quarter' => 'integer',
    ];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'initiated_by');
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(HrAppraisal::class, 'cycle_id');
    }

    public function corporateGoals(): HasMany
    {
        return $this->hasMany(HrCorporateGoal::class, 'cycle_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isInCalibration(): bool
    {
        return $this->status === 'calibration';
    }

    public function label(): string
    {
        return $this->name ?: sprintf('Q%d %d', $this->quarter, $this->fiscal_year);
    }
}
