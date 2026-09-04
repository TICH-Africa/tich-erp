<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLifecycleRequest extends Model
{
    public const TYPES = [
        'deferment' => 'Deferment',
        'withdrawal' => 'Course withdrawal',
        'readmission' => 'Program readmission',
    ];

    public const REVIEW_STATUSES = [
        'pending' => 'Pending',
        'partially_approved' => 'Partially approved',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'on_hold' => 'On hold',
    ];

    protected $fillable = [
        'student_id',
        'requested_by_user_id',
        'request_type',
        'status',
        'registrar_status',
        'dean_status',
        'effective_date',
        'deferment_months',
        'reason',
        'attachments',
        'reviewer_notes',
        'registrar_notes',
        'dean_notes',
        'reviewed_by_user_id',
        'registrar_reviewed_by_user_id',
        'dean_reviewed_by_user_id',
        'reviewed_at',
        'registrar_reviewed_at',
        'dean_reviewed_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'attachments' => 'array',
        'reviewed_at' => 'datetime',
        'registrar_reviewed_at' => 'datetime',
        'dean_reviewed_at' => 'datetime',
        'deferment_months' => 'integer',
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

    public function registrarReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_reviewed_by_user_id');
    }

    public function deanReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_reviewed_by_user_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->request_type] ?? ucfirst(str_replace('_', ' ', (string) $this->request_type));
    }

    public function statusLabel(): string
    {
        return self::REVIEW_STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function isOpenForReview(): bool
    {
        return in_array($this->status, ['pending', 'on_hold', 'partially_approved'], true);
    }

    public function syncOverallStatus(): void
    {
        $registrar = $this->registrar_status ?: 'pending';
        $dean = $this->dean_status ?: 'pending';

        if ($registrar === 'rejected' || $dean === 'rejected') {
            $this->status = 'rejected';
        } elseif ($registrar === 'approved' && $dean === 'approved') {
            $this->status = 'approved';
        } elseif ($registrar === 'on_hold' || $dean === 'on_hold') {
            $this->status = 'on_hold';
        } elseif ($registrar === 'approved' || $dean === 'approved') {
            $this->status = 'partially_approved';
        } else {
            $this->status = 'pending';
        }
    }
}
