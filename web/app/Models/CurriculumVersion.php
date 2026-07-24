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
        'program_id', 'academic_year_id', 'intake_year', 'intake_month',
        'version_label', 'version_number',
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

    public function intakeLabel(): string
    {
        if ($this->intake_year && $this->intake_month) {
            $month = date('M', mktime(0, 0, 0, (int) $this->intake_month, 1));

            return "{$month} {$this->intake_year} intake";
        }

        return $this->version_label;
    }

    /**
     * @return array<string, string>
     */
    public static function intakeMonths(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }
}
