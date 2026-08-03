<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffAllowance extends Model
{
    protected $table = 'staff_allowances';

    protected $fillable = [
        'staff_id',
        'allowance_type',
        'allowance_name',
        'amount',
        'effective_date',
        'end_date',
        'is_active',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }
}
