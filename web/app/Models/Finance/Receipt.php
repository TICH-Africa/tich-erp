<?php

namespace App\Models\Finance;

use App\Models\Student;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $table = 'receipts';

    protected $fillable = [
        'receipt_number',
        'payment_id',
        'invoice_id',
        'student_account_id',
        'student_id',
        'amount',
        'payment_method',
        'payment_reference',
        'issued_at',
        'issued_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

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

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'issued_by');
    }
}
