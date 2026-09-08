<?php

namespace App\Models\Me;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeQuarterlyReportLine extends Model
{
    protected $table = 'me_quarterly_report_lines';

    public $timestamps = false;

    protected $fillable = [
        'quarterly_report_id',
        'plan_output_id',
        'output',
        'activity',
        'costable_item',
        'planned',
        'achieved',
        'deviation',
        'display_order',
    ];

    protected $casts = [
        'planned' => 'decimal:2',
        'achieved' => 'decimal:2',
        'deviation' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MeQuarterlyReport::class, 'quarterly_report_id');
    }

    public function planOutput(): BelongsTo
    {
        return $this->belongsTo(MePlanOutput::class, 'plan_output_id');
    }

    public function isWarning(): bool
    {
        return (float) $this->deviation < 0;
    }
}
