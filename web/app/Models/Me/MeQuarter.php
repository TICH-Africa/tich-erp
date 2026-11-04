<?php

namespace App\Models\Me;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeQuarter extends Model
{
    protected $table = 'me_quarters';

    public $timestamps = false;

    protected $fillable = [
        'technical_plan_id',
        'quarter_number',
        'period_start',
        'period_end',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'created_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function technicalPlan(): BelongsTo
    {
        return $this->belongsTo(MeTechnicalPlan::class, 'technical_plan_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MeQuarterlyReport::class, 'quarter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function label(): string
    {
        return 'Q'.$this->quarter_number;
    }

    /**
     * M&E has cleared this quarter, so its outputs are baseline and cannot be revised.
     */
    public function isApproved(): bool
    {
        return $this->status === 'me_approved';
    }

    public function isAwaitingReview(): bool
    {
        return $this->status === 'me_review';
    }

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'me_review' => 'Awaiting M&E',
            'me_approved' => 'Approved by M&E',
            'returned' => 'Returned',
            default => 'Open',
        };
    }

    public function periodLabel(): string
    {
        if (! $this->period_start || ! $this->period_end) {
            return '';
        }

        return $this->period_start->format('d M Y').' - '.$this->period_end->format('d M Y');
    }

    public static function titleForQuarter(int $number): string
    {
        return 'Q'.$number;
    }

    /**
     * @return list<int>
     */
    public static function resolveQuarterFilter(int|string|null $quarter): array
    {
        if (is_int($quarter) && $quarter >= 1 && $quarter <= 4) {
            return [$quarter];
        }

        if (is_string($quarter) && ctype_digit($quarter) && (int) $quarter >= 1 && (int) $quarter <= 4) {
            return [(int) $quarter];
        }

        return [1, 2, 3, 4];
    }
}
