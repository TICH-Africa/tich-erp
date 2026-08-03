<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffBankAccount extends Model
{
    protected $table = 'staff_bank_accounts';

    protected $fillable = [
        'staff_id',
        'bank_name',
        'branch_name',
        'account_number',
        'account_name',
        'swift_code',
        'is_primary',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'verified_by');
    }
}
