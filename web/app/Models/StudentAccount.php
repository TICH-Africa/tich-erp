<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAccount extends Model
{
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
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
