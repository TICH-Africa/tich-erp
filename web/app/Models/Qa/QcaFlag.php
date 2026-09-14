<?php

namespace App\Models\Qa;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcaFlag extends Model
{
    protected $table = 'qca_flags';

    protected $fillable = [
        'flag_number', 'category', 'severity', 'status', 'description',
        'assigned_to', 'raised_by', 'target_entity_type', 'target_entity_id',
        'resolution_description', 'root_cause', 'evidence_of_correction',
        'resolution_deadline', 'resolved_at', 'resolved_by', 'resolution_notes',
        'ceo_override_reason', 'ceo_overridden_by', 'ceo_overridden_at',
        'downstream_locks', 'source_module', 'source_entity_id',
    ];

    protected $casts = [
        'downstream_locks' => 'array',
        'resolved_at' => 'datetime',
        'resolution_deadline' => 'date',
        'ceo_overridden_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'Training',
        'Evidence',
        'Attendance',
        'Assessment',
        'Accreditation',
        'Other',
    ];

    public const SEVERITIES = [
        'Low',
        'Medium',
        'High',
        'Critical',
    ];

    public const STATUSES = [
        'open',
        'in_progress',
        'resolved',
        'closed',
    ];

    public const MILESTONE_TYPES = [
        'Evidence Submitted',
        'Root Cause Identified',
        'Corrective Action Taken',
        'Awaiting External Review',
    ];

    public const SEVERITY_LOCK_MATRIX = [
        'Training' => [
            'High' => ['HR', 'Tutor Workspace', 'Student Portal'],
            'Critical' => ['HR', 'Tutor Workspace', 'Student Portal'],
        ],
        'Evidence' => [
            'High' => ['Grade Book', 'Student Portal'],
            'Critical' => ['Grade Book', 'Student Portal'],
        ],
        'Attendance' => [
            'Medium' => ['Exam Engine', 'Student Portal'],
            'High' => ['Exam Engine', 'Student Portal'],
            'Critical' => ['Exam Engine', 'Student Portal'],
        ],
        'Assessment' => [
            'High' => ['Student Portal'],
            'Critical' => ['Student Portal'],
        ],
        'Accreditation' => [
            'High' => ['Student Portal'],
            'Critical' => ['Student Portal'],
        ],
    ];

    public function milestones(): HasMany
    {
        return $this->hasMany(QcaMilestone::class, 'qca_flag_id')
            ->orderBy('created_at');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'raised_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'resolved_by');
    }

    public function ceoOverriddenBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'ceo_overridden_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isHighOrCritical(): bool
    {
        return in_array($this->severity, ['High', 'Critical'], true);
    }

    public function lockedModules(): array
    {
        if (! $this->isHighOrCritical()) {
            return [];
        }

        $matrix = self::SEVERITY_LOCK_MATRIX[$this->category] ?? [];
        $locks = $matrix[$this->severity] ?? [];

        if ($this->downstream_locks !== null) {
            $customLocks = $this->downstream_locks['modules'] ?? [];
            if ($customLocks !== []) {
                return $customLocks;
            }
        }

        return $locks;
    }

    public function isModuleLocked(string $module): bool
    {
        return in_array($module, $this->lockedModules(), true);
    }
}
