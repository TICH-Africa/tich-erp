<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramTimetableTemplate extends Model
{
    protected $table = 'program_timetable_templates';

    public $timestamps = false;

    protected $fillable = [
        'program_id', 'name', 'is_default', 'created_by', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(ProgramTimetableTemplateDay::class, 'template_id');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(ProgramTimetableSegment::class, 'template_id')
            ->orderBy('sort_order')
            ->orderBy('start_time');
    }

    /**
     * @return list<int>
     */
    public function activeDayNumbers(): array
    {
        return $this->days()
            ->where('is_active', 1)
            ->orderBy('day_of_week')
            ->pluck('day_of_week')
            ->map(fn ($day) => (int) $day)
            ->all();
    }
}
