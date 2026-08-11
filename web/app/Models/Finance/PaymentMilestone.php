<?php

namespace App\Models\Finance;

use App\Models\Student;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMilestone extends Model
{
    protected $table = 'payment_milestones';

    protected $fillable = [
        'student_account_id',
        'student_id',
        'invoice_id',
        'milestone_type',
        'percentage',
        'milestone_amount',
        'paid_amount',
        'status',
        'due_date',
        'paid_at',
        'recorded_by',
    ];

    protected $casts = [
        'milestone_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'percentage' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
