<?php

namespace App\Models\Finance;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    protected $table = 'installment_plans';

    protected $fillable = [
        'student_account_id',
        'student_id',
        'invoice_id',
        'plan_number',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
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

    public function items(): HasMany
    {
        return $this->hasMany(InstallmentPlanItem::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ((float) $this->total_amount <= 0) {
            return 0.0;
        }

        return round(((float) $this->paid_amount / (float) $this->total_amount) * 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDefaulted($query)
    {
        return $query->where('status', 'defaulted');
    }
}
