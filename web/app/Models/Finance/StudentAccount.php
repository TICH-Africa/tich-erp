<?php

namespace App\Models\Finance;

use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAccount extends Model
{
    protected $table = 'student_accounts';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'total_chargeable',
        'total_paid',
        'outstanding_balance',
        'work_study_credit',
        'scholarship_amount',
        'helb_amount',
        'sponsor_amount',
        'credit_balance',
        'is_cleared',
        'cleared_at',
        'last_payment_date',
    ];

    protected $casts = [
        'total_chargeable' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'work_study_credit' => 'decimal:2',
        'scholarship_amount' => 'decimal:2',
        'helb_amount' => 'decimal:2',
        'sponsor_amount' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'is_cleared' => 'boolean',
        'cleared_at' => 'datetime',
        'last_payment_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(FinancialAdjustment::class);
    }

    public function installmentPlans(): HasMany
    {
        return $this->hasMany(InstallmentPlan::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(PaymentMilestone::class);
    }

    public function getNetObligationAttribute(): float
    {
        return (float) ($this->total_chargeable - ($this->scholarship_amount + $this->helb_amount + $this->sponsor_amount + $this->work_study_credit));
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->is_cleared) {
            return 'CLEARED';
        }

        if ((float) $this->outstanding_balance <= 0 && (float) $this->credit_balance > 0) {
            return 'CREDIT';
        }

        return 'NOT CLEARED';
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public static function openForStudent(int $studentId, int $academicYearId): self
    {
        return static::firstOrCreate(
            [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
            ],
            [
                'total_chargeable' => 0,
                'total_paid' => 0,
                'outstanding_balance' => 0,
                'credit_balance' => 0,
                'is_cleared' => false,
            ]
        );
    }
}
