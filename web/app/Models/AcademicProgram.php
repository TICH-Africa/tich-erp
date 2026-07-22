<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicProgram extends Model
{
    protected $table = 'academic_programs';

    public $timestamps = false;

    protected $fillable = [
        'program_code', 'program_name', 'program_type', 'regulatory_body', 'curriculum_format',
        'department_id', 'duration_months', 'semester_count', 'block_count',
        'is_nursing_track', 'min_attendance_pct', 'theory_pass_mark', 'clinical_pass_mark',
        'status', 'approved_by_ceo_at', 'approved_by_ceo_id',
        'is_featured_on_homepage', 'homepage_display_order',
        'homepage_tagline', 'entry_requirements', 'created_by',
    ];

    protected $casts = [
        'is_featured_on_homepage' => 'boolean',
        'is_nursing_track' => 'boolean',
        'min_attendance_pct' => 'decimal:2',
        'theory_pass_mark' => 'decimal:2',
        'clinical_pass_mark' => 'decimal:2',
        'approved_by_ceo_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'program_id');
    }

    public function programUnits(): HasMany
    {
        return $this->hasMany(ProgramUnit::class, 'program_id')
            ->orderBy('display_order')
            ->orderBy('priority');
    }

    public function mappedUnits(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'program_units', 'program_id', 'unit_id')
            ->withPivot(['semester', 'block_id', 'is_compulsory', 'display_order', 'priority', 'contact_hours', 'total_learning_hours']);
    }

    public function curriculumVersions(): HasMany
    {
        return $this->hasMany(CurriculumVersion::class, 'program_id')->orderByDesc('version_number');
    }

    public function nursingBlocks(): HasMany
    {
        return $this->hasMany(NursingBlock::class, 'program_id')->orderBy('block_order');
    }

    public function usesBlocks(): bool
    {
        return $this->curriculum_format === 'block' || (bool) $this->is_nursing_track;
    }
}
