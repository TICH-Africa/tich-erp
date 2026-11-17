<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaC2bTransaction extends Model
{
    public const KIND_VALIDATION = 'validation';

    public const KIND_CONFIRMATION = 'confirmation';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_UNMATCHED = 'unmatched';

    public const OUTCOMES = [
        self::STATUS_BOOKED => 'Booked',
        self::STATUS_UNMATCHED => 'Needs reconciling',
        self::STATUS_DUPLICATE => 'Already recorded',
        self::STATUS_REFUSED => 'Refused',
        self::STATUS_ACCEPTED => 'Allowed to pay',
        'received' => 'Awaiting posting',
    ];

    protected $fillable = [
        'kind',
        'transaction_id',
        'receipt_number',
        'local_trxn_id',
        'bill_ref_number',
        'invoice_number',
        'msisdn',
        'amount',
        'status',
        'result_code',
        'result_desc',
        'student_id',
        'payment_id',
        'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isBooked(): bool
    {
        return $this->status === self::STATUS_BOOKED;
    }

    public function outcomeLabel(): string
    {
        return self::OUTCOMES[$this->status] ?? (string) $this->status;
    }

    public function allocationLabel(): string
    {
        $paymentNumber = $this->payment?->payment_number;

        return match ($this->status) {
            self::STATUS_BOOKED => $paymentNumber ? 'On payment '.$paymentNumber : 'On the student account',
            self::STATUS_DUPLICATE => $paymentNumber ? 'Already on payment '.$paymentNumber : 'Already on the student account',
            self::STATUS_UNMATCHED => 'Not allocated - Finance to reconcile',
            self::STATUS_REFUSED => 'Nothing taken',
            self::STATUS_ACCEPTED => 'No payment reported yet',
            default => 'Waiting to be posted',
        };
    }
}
