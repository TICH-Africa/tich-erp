<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicWorkplanActivity extends Model
{
    protected $table = 'academic_workplan_activities';

    protected $fillable = [
        'workplan_id',
        'activity',
        'timeline_start',
        'timeline_end',
        'kpi',
        'resources',
        'sort_order',
    ];

    protected $casts = [
        'timeline_start' => 'date',
        'timeline_end' => 'date',
    ];

    public function workplan(): BelongsTo
    {
        return $this->belongsTo(AcademicWorkplan::class, 'workplan_id');
    }
}
