<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountExpenseEntry extends Model
{
    protected $table = 'account_expense_entries';

    protected $fillable = [
        'account_code',
        'reference_no',
        'expense_date',
        'payee_name',
        'address',
        'memo',
        'class',
        'currency',
        'exchange_rate',
        'amount',
        'vat_amount',
        'supplier_vat_code',
        'credit_account_code',
        'recorded_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_code', 'account_code');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'credit_account_code', 'account_code');
    }
}
