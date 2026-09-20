<?php

namespace App\Models\Me;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MePlanOutput extends Model
{
    protected $table = 'me_plan_outputs';

    public $timestamps = false;

    protected $fillable = [
        'technical_plan_id',
        'output',
        'activity',
        'costable_item',
        'planned',
        'planned_unit',
        'quarter',
        'display_order',
        'created_at',
    ];

    protected $casts = [
        'planned' => 'decimal:2',
        'quarter' => 'integer',
        'created_at' => 'datetime',
    ];

    public function technicalPlan(): BelongsTo
    {
        return $this->belongsTo(MeTechnicalPlan::class, 'technical_plan_id');
    }
}
