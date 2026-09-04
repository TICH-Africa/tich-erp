<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfileChangeRequest extends Model
{
    public const TYPE_PROFILE_UPDATE = 'profile_update';

    public const TYPE_PHOTO = 'photo';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_id',
        'requested_by_user_id',
        'request_type',
        'status',
        'current_snapshot',
        'proposed_changes',
        'attachment_path',
        'student_notes',
        'reviewer_notes',
        'rejection_reason',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'current_snapshot' => 'array',
        'proposed_changes' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
