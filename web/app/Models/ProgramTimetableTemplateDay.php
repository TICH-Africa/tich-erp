<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramTimetableTemplateDay extends Model
{
    protected $table = 'program_timetable_template_days';

    public $timestamps = false;

    protected $fillable = [
        'template_id', 'day_of_week', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProgramTimetableTemplate::class, 'template_id');
    }
}
