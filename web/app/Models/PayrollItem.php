<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payroll_run_id',
        'payslip_number',
        'staff_id',
        'pay_period_year',
        'pay_period_month',
        'basic_salary',
        'gross_salary',
        'total_allowances',
        'total_deductions',
        'net_salary',
        'calculation_snapshot',
        'is_processed',
        'processed_by',
        'processed_at',
        'is_approved',
        'approved_by',
        'approved_at',
        'is_disbursed',
        'disbursement_date',
        'eft_reference',
        'bank_transaction_id',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'calculation_snapshot' => 'array',
        'is_processed' => 'boolean',
        'is_approved' => 'boolean',
        'is_disbursed' => 'boolean',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'disbursement_date' => 'date',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function statutoryDeductions(): HasMany
    {
        return $this->hasMany(StatutoryDeduction::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'processed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function breakdown(): array
    {
        return $this->calculation_snapshot ?? [];
    }
}
