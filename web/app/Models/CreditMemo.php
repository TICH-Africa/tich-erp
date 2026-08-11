<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditMemo extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'credit_memo_number',
        'invoice_id',
        'student_account_id',
        'student_id',
        'amount',
        'reason',
        'status',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'issued_by');
    }
}
