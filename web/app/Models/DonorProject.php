<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonorProject extends Model
{
    public $timestamps = false;

    protected $table = 'donor_projects';

    protected $fillable = [
        'project_code',
        'project_name',
        'donor_name',
        'donor_type',
        'total_grant_amount',
        'currency',
        'disbursed_amount',
        'disbursement_currency',
        'exchange_rate_at_disbursement',
        'kes_equivalent',
        'start_date',
        'end_date',
        'project_leader_id',
        'status',
    ];

    protected $casts = [
        'total_grant_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'exchange_rate_at_disbursement' => 'decimal:4',
        'kes_equivalent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'project_leader_id');
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(DonorDisbursement::class);
    }

    public function disbursedKesTotal(): float
    {
        return (float) $this->disbursements()->sum('kes_amount');
    }
}
