<?php

namespace App\Models\Qa;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaComplianceScore extends Model
{
    protected $table = 'qa_compliance_scores';

    public $timestamps = false;

    protected $fillable = [
        'qa_plan_id', 'department_id', 'total_items', 'items_submitted',
        'weighted_score', 'pass_fail_status', 'is_below_threshold',
        'threshold_met_at', 'calculated_at',
    ];

    protected $casts = [
        'weighted_score' => 'decimal:2',
        'is_below_threshold' => 'boolean',
        'threshold_met_at' => 'datetime',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(QaPlan::class, 'qa_plan_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
