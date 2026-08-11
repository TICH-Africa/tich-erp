<?php

namespace App\Models\Finance;

use App\Models\Student;
use App\Models\Semester;
use App\Models\Finance\FeeStructure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'student_account_id',
        'student_id',
        'semester_id',
        'fee_structure_id',
        'invoice_type',
        'description',
        'amount',
        'amount_paid',
        'balance',
        'issue_date',
        'due_date',
        'status',
        'payment_gateway_ref',
        'is_sent_to_portal',
        'sent_at',
        'waiver_reason',
        'waived_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'is_sent_to_portal' => 'boolean',
        'sent_at' => 'datetime',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'waived_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute(): bool
    {
        return ! in_array($this->status, ['paid', 'waived']) && $this->due_date < now()->toDateString();
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['issued', 'partial', 'overdue']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')->orWhere(function ($q) {
            $q->whereIn('status', ['issued', 'partial'])->where('due_date', '<', now()->toDateString());
        });
    }
}
