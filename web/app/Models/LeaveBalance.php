<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';

    /** Table uses `last_updated` (and DB-default `created_at`); no `updated_at` column. */
    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'carried_forward_days',
        'days_taken',
        'days_pending',
        'balance_days',
        'last_updated',
    ];

    protected $casts = [
        'entitled_days' => 'decimal:2',
        'carried_forward_days' => 'decimal:2',
        'days_taken' => 'decimal:2',
        'days_pending' => 'decimal:2',
        'balance_days' => 'decimal:2',
        'last_updated' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
