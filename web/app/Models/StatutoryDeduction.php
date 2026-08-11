<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryDeduction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payroll_item_id',
        'staff_id',
        'deduction_type',
        'gross_salary_for_deduction',
        'deduction_rate',
        'employer_contribution',
        'employee_amount',
        'employer_amount',
        'is_remitted',
        'remittance_date',
        'remittance_reference',
        'kra_reference',
    ];

    protected $casts = [
        'gross_salary_for_deduction' => 'decimal:2',
        'deduction_rate' => 'decimal:2',
        'employer_contribution' => 'decimal:2',
        'employee_amount' => 'decimal:2',
        'employer_amount' => 'decimal:2',
        'is_remitted' => 'boolean',
        'remittance_date' => 'date',
    ];

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function label(): string
    {
        return match ($this->deduction_type) {
            'paye' => 'PAYE (KRA)',
            'nssf' => 'NSSF',
            'sha' => 'SHA/SHIF',
            'ahl' => 'AHL',
            'withholding_tax' => 'Withholding tax',
            default => ucwords(str_replace('_', ' ', $this->deduction_type)),
        };
    }
}
