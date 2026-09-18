<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffBankAccount extends Model
{
    protected $table = 'staff_bank_accounts';

    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'account_name',
        'account_number',
        'bank_name',
        'bank_branch',
        'bank_code',
        'is_primary',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
