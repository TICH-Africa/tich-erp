<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramTier extends Model
{
    protected $fillable = [
        'program_id',
        'tier_key',
        'tier_order',
        'cumulative_months',
        'learning_outcomes',
        'marketing_summary',
    ];

    protected $casts = [
        'tier_order' => 'integer',
        'cumulative_months' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function label(): string
    {
        return config('tich-program-tiers.tier_labels.'.$this->tier_key, ucfirst(str_replace('_', ' ', $this->tier_key)));
    }
}
