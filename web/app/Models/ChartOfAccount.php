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

    public function isTopLevel(): bool
    {
        return $this->parent_account_code === null || $this->parent_account_code === '';
    }

    /**
     * Every descendant account code, breadth first, cycle safe.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function descendantCodes()
    {
        $codes = collect();
        $queue = $this->children()->pluck('account_code');
        $seen = [];

        while ($queue->isNotEmpty()) {
            $code = $queue->shift();

            if (isset($seen[$code])) {
                continue;
            }

            $seen[$code] = true;
            $codes->push($code);
            $queue = $queue->merge(self::query()->where('parent_account_code', $code)->pluck('account_code'));
        }

        return $codes;
    }

    /**
     * Ancestor accounts ordered from the top level account down to the direct parent.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public function ancestors()
    {
        $chain = collect();
        $code = $this->parent_account_code;
        $seen = [$this->account_code => true];

        while ($code !== null && $code !== '' && ! isset($seen[$code])) {
            $seen[$code] = true;
            $parent = self::query()->where('account_code', $code)->first();

            if ($parent === null) {
                break;
            }

            $chain->push($parent);
            $code = $parent->parent_account_code;
        }

        return $chain->reverse()->values();
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->account_type, ['asset', 'expense'], true);
    }
}
