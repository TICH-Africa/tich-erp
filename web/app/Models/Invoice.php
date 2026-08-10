<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'student_account_id',
        'student_id',
        'semester_id',
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
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'is_sent_to_portal' => 'boolean',
        'sent_at' => 'datetime',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPayable(): bool
    {
        return in_array($this->status, ['issued', 'partial', 'overdue'], true) && (float) $this->balance > 0;
    }
}
