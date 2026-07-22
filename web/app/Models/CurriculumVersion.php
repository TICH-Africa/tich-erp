<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumVersion extends Model
{
    protected $table = 'curriculum_versions';

    public $timestamps = false;

    protected $fillable = [
        'program_id', 'academic_year_id', 'version_label', 'version_number',
        'curriculum_format', 'status', 'notes',
        'created_by', 'submitted_at', 'submitted_by',
        'registrar_approved_at', 'registrar_approved_by',
        'ceo_approved_at', 'ceo_approved_by',
        'published_at', 'published_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'registrar_approved_at' => 'datetime',
        'ceo_approved_at' => 'datetime',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CurriculumVersionUnit::class, 'curriculum_version_id')
            ->orderBy('display_order')
            ->orderBy('priority');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
