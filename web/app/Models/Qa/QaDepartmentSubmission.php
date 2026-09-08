<?php

namespace App\Models\Qa;

use App\Models\Department;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QaDepartmentSubmission extends Model
{
    protected $table = 'qa_department_submissions';

    public $timestamps = false;

    protected $fillable = [
        'qa_plan_id', 'checklist_item_id', 'department_id', 'submitted_by',
        'submission_text', 'score', 'submission_status',
        'verified_by', 'verified_at', 'verified_notes', 'submitted_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'verified_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(QaPlan::class, 'qa_plan_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(QaAuditChecklist::class, 'checklist_item_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function submittedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'submitted_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(QaEvidenceAttachment::class, 'linked_id')
            ->where('evidence_type', 'checklist_submission');
    }

    public function isEditable(): bool
    {
        return in_array($this->submission_status, ['pending', 'draft', 'rejected'], true);
    }
}
