<?php

namespace App\Models\Qa;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaAuditChecklist extends Model
{
    protected $table = 'qa_audit_checklists';

    public $timestamps = false;

    protected $fillable = [
        'qa_plan_id', 'checklist_item_text', 'item_category',
        'weight', 'max_score', 'applies_to_department_id',
        'requires_evidence', 'display_order', 'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'requires_evidence' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(QaPlan::class, 'qa_plan_id');
    }

    public function appliesToDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'applies_to_department_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(QaDepartmentSubmission::class, 'checklist_item_id');
    }
}
