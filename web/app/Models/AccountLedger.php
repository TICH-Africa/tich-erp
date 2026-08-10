<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLedger extends Model
{
    public $timestamps = false;

    protected $table = 'account_ledger';

    protected $fillable = [
        'ledger_date',
        'transaction_type',
        'debit_account_code',
        'credit_account_code',
        'debit_amount',
        'credit_amount',
        'narration',
        'reference_table',
        'reference_id',
        'source_module',
        'is_reversed',
        'reversal_ledger_id',
        'recorded_by',
    ];

    protected $casts = [
        'ledger_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'is_reversed' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
