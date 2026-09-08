<?php

namespace App\Models\Me;

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
        'created_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
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

    public function label(): string
    {
        return 'Q'.$this->quarter_number;
    }
}
