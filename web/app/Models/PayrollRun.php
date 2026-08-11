<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'run_number',
        'pay_period_year',
        'pay_period_month',
        'status',
        'staff_count',
        'total_gross',
        'total_deductions',
        'total_net',
        'total_paye',
        'total_nssf',
        'total_sha',
        'total_ahl',
        'total_employer_cost',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'gl_reference',
    ];

    protected $casts = [
        'pay_period_year' => 'integer',
        'pay_period_month' => 'integer',
        'staff_count' => 'integer',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_paye' => 'decimal:2',
        'total_nssf' => 'decimal:2',
        'total_sha' => 'decimal:2',
        'total_ahl' => 'decimal:2',
        'total_employer_cost' => 'decimal:2',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'posted_by');
    }

    public function periodLabel(): string
    {
        return \Illuminate\Support\Carbon::create($this->pay_period_year, $this->pay_period_month, 1)
            ->format('F Y');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->staff_count > 0;
    }

    public function canPostToGl(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
