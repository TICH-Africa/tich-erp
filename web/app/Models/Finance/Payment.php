<?php

namespace App\Models\Finance;

use App\Models\Student;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'student_account_id',
        'student_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_reference',
        'transaction_channel_ref',
        'mpesa_receipt_number',
        'status',
        'is_reconciled',
        'reconciled_by',
        'reconciled_at',
        'recorded_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'reconciled_at' => 'datetime',
        'is_reconciled' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reconciled_by');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }

    public function receipt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function allocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'SUCCESS';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['INITIATED', 'PENDING'], true);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'SUCCESS');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['INITIATED', 'PENDING']);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
