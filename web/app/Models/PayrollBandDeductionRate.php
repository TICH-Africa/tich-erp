<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollBandDeductionRate extends Model
{
    protected $fillable = [
        'payroll_tax_band_id',
        'payroll_deduction_type_id',
        'rate_percent',
    ];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:4',
        ];
    }

    public function band(): BelongsTo
    {
        return $this->belongsTo(PayrollTaxBand::class, 'payroll_tax_band_id');
    }

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(PayrollDeductionType::class, 'payroll_deduction_type_id');
    }
}
