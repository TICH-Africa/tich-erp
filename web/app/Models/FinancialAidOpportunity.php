<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAidOpportunity extends Model
{
    use SoftDeletes;

    protected $table = 'financial_aid_opportunities';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'eligibility_criteria',
        'application_process',
        'amount',
        'funding_type',
        'application_open_date',
        'application_deadline',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'application_open_date' => 'date',
        'application_deadline' => 'date',
        'published_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(FinancialAidApplication::class, 'financial_aid_opportunity_id');
    }

    public function isOpenForApplications(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }
        if ($this->application_open_date && $this->application_open_date > now()) {
            return false;
        }
        if ($this->application_deadline && $this->application_deadline < now()) {
            return false;
        }
        return true;
    }

    /**
     * A round the school has already finished with: either HR closed it by hand or its deadline
     * passed. It stays on the website so a past awardee or a missed applicant can still find it.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'closed'
            || ($this->application_deadline !== null && $this->application_deadline->endOfDay() < now());
    }

    public function websiteStatusLabel(): string
    {
        if ($this->isCompleted()) {
            return 'Completed';
        }

        return $this->application_open_date && $this->application_open_date > now()
            ? 'Opens '.$this->application_open_date->format('d M Y')
            : 'Open';
    }

    public function fundingTypeLabel(): string
    {
        return match ($this->funding_type) {
            'work_study' => 'Work-Study',
            'loan' => 'Loan',
            'grant' => 'Grant',
            default => 'Scholarship',
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}