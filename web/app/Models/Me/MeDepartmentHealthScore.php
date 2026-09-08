<?php

namespace App\Models\Me;

use App\Models\Administration\PlanningCycle;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeDepartmentHealthScore extends Model
{
    protected $table = 'me_department_health_scores';

    public $timestamps = false;

    protected $fillable = [
        'department_id',
        'fiscal_year',
        'planning_cycle_id',
        'qa_compliance_avg',
        'me_achievement_avg',
        'health_score',
        'health_rating',
        'calculated_at',
        'created_at',
    ];

    protected $casts = [
        'qa_compliance_avg' => 'decimal:2',
        'me_achievement_avg' => 'decimal:2',
        'health_score' => 'decimal:2',
        'calculated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function planningCycle(): BelongsTo
    {
        return $this->belongsTo(PlanningCycle::class, 'planning_cycle_id');
    }
}
