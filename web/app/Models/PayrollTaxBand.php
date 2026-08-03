<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollTaxBand extends Model
{
    protected $fillable = [
        'label',
        'min_amount',
        'max_amount',
        'rate_percent',
        'display_order',
        'is_active',
        'effective_from',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'rate_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'effective_from' => 'date',
        ];
    }

    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', 1)->orderBy('display_order')->orderBy('min_amount');
    }
}
