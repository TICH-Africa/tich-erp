<?php

namespace App\Models\Qa;

use App\Models\Department;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaPlan extends Model
{
    protected $table = 'qa_plans';

    protected $fillable = [
        'plan_name', 'description', 'instructions',
        'period_start', 'period_end', 'due_at', 'pass_threshold',
        'scope_type', 'department_ids',
        'deployed_by', 'deployed_at', 'dispatched_at', 'compiled_at',
        'created_by', 'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'due_at' => 'datetime',
        'pass_threshold' => 'decimal:2',
        'department_ids' => 'array',
        'deployed_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'compiled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft',
        'dispatched',
        'in_progress',
        'compiled',
        'closed',
        'cancelled',
    ];

    public function checklists(): HasMany
    {
        return $this->hasMany(QaAuditChecklist::class, 'qa_plan_id')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(QaDepartmentSubmission::class, 'qa_plan_id');
    }

    public function complianceScores(): HasMany
    {
        return $this->hasMany(QaComplianceScore::class, 'qa_plan_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(QaCorrectiveAction::class, 'qa_plan_id');
    }

    public function deployedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'deployed_by');
    }

    public function createdByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isOpenForSubmission(): bool
    {
        return in_array($this->status, ['dispatched', 'in_progress'], true);
    }

    /**
     * @return list<int>
     */
    public function targetDepartmentIds(): array
    {
        return array_values(array_map('intval', $this->department_ids ?? []));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Department>
     */
    public function targetDepartments()
    {
        $ids = $this->targetDepartmentIds();
        if ($ids === []) {
            return collect();
        }

        return Department::query()
            ->whereIn('id', $ids)
            ->orderBy('dept_name')
            ->get();
    }
}
