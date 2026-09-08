<?php

namespace App\Models\Me;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeQuarterlyReport extends Model
{
    protected $table = 'me_quarterly_reports';

    protected $fillable = [
        'technical_plan_id',
        'quarter_id',
        'department_id',
        'status',
        'submitted_by',
        'submitted_at',
        'me_verified_by',
        'me_verified_at',
        'me_notes',
        'ceo_delivered_at',
        'ceo_reviewed_by',
        'ceo_reviewed_at',
        'ceo_signature',
        'ceo_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'me_verified_at' => 'datetime',
        'ceo_delivered_at' => 'datetime',
        'ceo_reviewed_at' => 'datetime',
    ];

    public function technicalPlan(): BelongsTo
    {
        return $this->belongsTo(MeTechnicalPlan::class, 'technical_plan_id');
    }

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(MeQuarter::class, 'quarter_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function meVerifier(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'me_verified_by');
    }

    public function ceoReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ceo_reviewed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(MeQuarterlyReportLine::class, 'quarterly_report_id')->orderBy('display_order');
    }

    public function totalPlanned(): float
    {
        return (float) $this->lines->sum('planned');
    }

    public function totalAchieved(): float
    {
        return (float) $this->lines->sum('achieved');
    }

    public function achievementRate(): ?float
    {
        $planned = $this->totalPlanned();
        if ($planned <= 0) {
            return null;
        }

        return round(($this->totalAchieved() / $planned) * 100, 2);
    }
}
