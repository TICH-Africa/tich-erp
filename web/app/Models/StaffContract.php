<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffContract extends Model
{
    protected $table = 'staff_contracts';

    protected $fillable = [
        'contract_number',
        'staff_id',
        'contract_type',
        'job_title',
        'department_id',
        'gross_salary',
        'start_date',
        'end_date',
        'is_renewable',
        'renewal_notice_sent',
        'renewal_notice_date',
        'renewal_status',
        'new_contract_id',
        'probation_end_date',
        'probation_status',
        'contract_document_path',
        'is_signed',
        'signed_date',
        'witnessed_by',
    ];

    protected $casts = [
        'gross_salary' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'probation_end_date' => 'date',
        'signed_date' => 'date',
        'renewal_notice_date' => 'date',
        'is_renewable' => 'boolean',
        'renewal_notice_sent' => 'boolean',
        'is_signed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function newContract(): BelongsTo
    {
        return $this->belongsTo(StaffContract::class, 'new_contract_id');
    }

    public function previousContract(): HasMany
    {
        return $this->hasMany(StaffContract::class, 'new_contract_id');
    }

    public function isExpired(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return $this->end_date->lt(now());
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return $this->end_date->between(now(), now()->addDays($days));
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        });
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where(function ($q) use ($days) {
            $q->whereNotNull('end_date')
                ->where('end_date', '<=', now()->addDays($days))
                ->where('end_date', '>=', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }
}
