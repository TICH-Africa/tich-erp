<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlan extends Model
{
    protected $table = 'lesson_plans';

    public $timestamps = false;

    protected $fillable = [
        'plan_number',
        'unit_allocation_id',
        'prepared_by',
        'source_type',
        'lesson_title',
        'lesson_objectives',
        'topics_covered',
        'competencies_targeted',
        'contact_hours',
        'week_number',
        'planned_date',
        'teaching_methods',
        'resources_required',
        'uploaded_file_path',
        'uploaded_file_name',
        'form_payload',
        'tutor_verified_at',
        'status',
        'hod_comments',
        'hod_id',
        'hod_action_at',
        'registrar_visible',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'hod_action_at' => 'datetime',
        'tutor_verified_at' => 'datetime',
        'form_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(UnitAllocation::class, 'unit_allocation_id');
    }

    public function preparedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'prepared_by');
    }

    public function hodStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'hod_id');
    }

    public function approvals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LessonPlanApproval::class)->orderByDesc('decided_at');
    }

    public function isEditableByTutor(): bool
    {
        return in_array($this->status, ['draft', 'modified', 'rejected'], true);
    }

    public function isFormBased(): bool
    {
        return ($this->source_type ?? 'form') === 'form';
    }

    public function isUploadBased(): bool
    {
        return ($this->source_type ?? 'form') === 'upload';
    }

    public function isVerifiedByTutor(): bool
    {
        return $this->tutor_verified_at !== null;
    }

    public function isReadyToSubmit(): bool
    {
        if (! $this->isEditableByTutor()) {
            return false;
        }

        if ($this->isUploadBased()) {
            return filled($this->uploaded_file_path);
        }

        return $this->isVerifiedByTutor();
    }
}
