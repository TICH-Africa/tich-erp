<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveSickPayAdjustment extends Model
{
    protected $table = 'leave_sick_pay_adjustments';

    protected $fillable = [
        'leave_request_id',
        'staff_id',
        'year',
        'month',
        'half_pay_days',
        'daily_rate',
        'deduction_amount',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
