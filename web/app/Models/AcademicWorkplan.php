<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicWorkplan extends Model
{
    protected $table = 'academic_workplans';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    public const REVIEW_PENDING = 'pending';

    public const REVIEW_APPROVED = 'approved';

    public const REVIEW_REJECTED = 'rejected';

    public const REVIEW_CHANGES_REQUESTED = 'changes_requested';

    protected $fillable = [
        'workplan_number',
        'department_id',
        'semester_id',
        'title',
        'objectives',
        'resources',
        'kpis',
        'status',
        'prepared_by_staff_id',
        'submitted_at',
        'registrar_status',
        'registrar_staff_id',
        'registrar_acted_at',
        'registrar_comments',
        'qa_status',
        'qa_staff_id',
        'qa_acted_at',
        'qa_comments',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'registrar_acted_at' => 'datetime',
        'qa_acted_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function preparedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'prepared_by_staff_id');
    }

    public function registrarStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'registrar_staff_id');
    }

    public function qaStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'qa_staff_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AcademicWorkplanActivity::class, 'workplan_id')->orderBy('sort_order');
    }

    public function isEditableByHod(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_CHANGES_REQUESTED,
            self::STATUS_REJECTED,
        ], true);
    }

    public function isSubmittable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_CHANGES_REQUESTED,
            self::STATUS_REJECTED,
            self::STATUS_PENDING,
        ], true);
    }

    public function registrarPending(): bool
    {
        return $this->registrar_status === self::REVIEW_PENDING;
    }

    public function qaPending(): bool
    {
        return $this->qa_status === self::REVIEW_PENDING;
    }
}
