<?php

namespace App\Models\Qa;

use App\Models\Department;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaCorrectiveAction extends Model
{
    protected $table = 'qa_corrective_actions';

    protected $fillable = [
        'qa_plan_id', 'department_id', 'checklist_item_id',
        'flagged_reason', 'compliance_score_at_flag', 'resolution_deadline',
        'resolution_plan', 'responsible_officer_id', 'status',
        'resolved_at', 'resolved_by', 'resolution_notes', 'is_module_lock_active',
    ];

    protected $casts = [
        'compliance_score_at_flag' => 'decimal:2',
        'resolution_deadline' => 'date',
        'resolved_at' => 'datetime',
        'is_module_lock_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(QaPlan::class, 'qa_plan_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(QaAuditChecklist::class, 'checklist_item_id');
    }

    public function responsibleOfficer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_officer_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(QaEvidenceAttachment::class, 'linked_id')
            ->where('evidence_type', 'corrective_action');
    }
}
