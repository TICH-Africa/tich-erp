<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffboardingRequest extends Model
{
    protected $table = 'offboarding_requests';

    protected $fillable = [
        'staff_id',
        'exit_type',
        'status',
        'exit_date',
        'notice_period_days',
        'last_working_day',
        'reason',
        'termination_reason',
        'initiated_by',
        'approved_by',
        'approved_at',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'exit_date' => 'date',
        'notice_period_days' => 'integer',
        'last_working_day' => 'date',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'initiated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'processed_by');
    }

    public function clearanceItems(): HasMany
    {
        return $this->hasMany(ClearanceChecklistItem::class);
    }
}
