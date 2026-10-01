<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    public $timestamps = false;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'currency',
        'parent_account_code',
        'is_active',
        'is_system_account',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system_account' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_account_code', 'account_code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_account_code', 'account_code');
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(AccountLedger::class, 'debit_account_code', 'account_code');
    }

    public function creditEntries(): HasMany
    {
        return $this->hasMany(AccountLedger::class, 'credit_account_code', 'account_code');
    }

    /**
     * Ledger rows where this account is either the debit or the credit side.
     */
    public function ledgerEntries(): Builder
    {
        $code = $this->account_code;

        return AccountLedger::query()->where(function (Builder $query) use ($code) {
            $query->where('debit_account_code', $code)
                ->orWhere('credit_account_code', $code);
        });
    }

    public function hasLedgerEntries(): bool
    {
        return $this->ledgerEntries()->exists();
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->account_type, ['asset', 'expense'], true);
    }
}
