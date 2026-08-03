<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollDeductionType extends Model
{
    protected $fillable = [
        'code',
        'label',
        'value_type',
        'fixed_amount',
        'employer_rate_percent',
        'reduces_taxable',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fixed_amount' => 'decimal:2',
            'employer_rate_percent' => 'decimal:4',
            'reduces_taxable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function bandRates(): HasMany
    {
        return $this->hasMany(PayrollBandDeductionRate::class);
    }

    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', 1)->orderBy('display_order')->orderBy('label');
    }

    public function isBandPercent(): bool
    {
        return $this->value_type === 'band_percent';
    }

    public function isGlobalFixed(): bool
    {
        return $this->value_type === 'global_fixed';
    }
}
