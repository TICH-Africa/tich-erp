<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollStatutoryRate extends Model
{
    protected $fillable = [
        'code',
        'label',
        'rate_percent',
        'employer_rate_percent',
        'fixed_amount',
        'floor_amount',
        'ceiling_amount',
        'applies_to',
        'notes',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:4',
            'employer_rate_percent' => 'decimal:4',
            'fixed_amount' => 'decimal:2',
            'floor_amount' => 'decimal:2',
            'ceiling_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', 1)->orderBy('display_order')->orderBy('code');
    }
}
