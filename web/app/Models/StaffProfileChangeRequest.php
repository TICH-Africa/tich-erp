<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfileChangeRequest extends Model
{
    use PrunesStoredFiles;

    /** @var array<string, string> */
    protected array $storedFiles = [
        'attachment_path' => 'public',
    ];
    public const TYPE_PROFILE_UPDATE = 'profile_update';

    public const TYPE_PHOTO = 'photo';

    public const TYPE_QUALIFICATION = 'qualification';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'staff_id',
        'requested_by_user_id',
        'request_type',
        'status',
        'current_snapshot',
        'proposed_changes',
        'attachment_path',
        'employee_notes',
        'hr_notes',
        'rejection_reason',
        'reviewed_by_staff_id',
        'reviewed_at',
    ];

    protected $casts = [
        'current_snapshot' => 'array',
        'proposed_changes' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by_staff_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function typeLabel(): string
    {
        return match ($this->request_type) {
            self::TYPE_PHOTO => 'Profile photo',
            self::TYPE_QUALIFICATION => 'Qualification / certificate',
            default => 'Profile details',
        };
    }
}
