<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use App\Services\Academics\ProgramTierPlannerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicProgram extends Model
{
    use PrunesStoredFiles;

    protected $table = 'academic_programs';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'cover_image_path' => 'public',
    ];

    public $timestamps = false;

    protected $fillable = [
        'program_code', 'program_name', 'program_type', 'regulatory_body', 'curriculum_format',
        'is_tiered', 'tier_semester_months',
        'department_id', 'duration_months', 'semester_count', 'block_count',
        'is_nursing_track', 'min_attendance_pct', 'theory_pass_mark', 'clinical_pass_mark',
        'status', 'approved_by_ceo_at', 'approved_by_ceo_id',
        'is_featured_on_homepage', 'homepage_display_order',
        'homepage_tagline', 'cover_image_path', 'entry_requirements', 'created_by',
    ];

    protected $casts = [
        'is_featured_on_homepage' => 'boolean',
        'is_tiered' => 'boolean',
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

    public function tiers(): HasMany
    {
        return $this->hasMany(ProgramTier::class, 'program_id')->orderBy('tier_order');
    }

    public function isTieredProgram(): bool
    {
        return (bool) $this->is_tiered;
    }

    public function tierExitLabel(?string $tierKey): ?string
    {
        if (! filled($tierKey)) {
            return null;
        }

        return config('tich-program-tiers.tier_labels.'.$tierKey, $tierKey);
    }

    /**
     * Short ladder for tables, e.g. "Certificate → Diploma → Advanced Diploma".
     */
    public function tierLadderSummary(): ?string
    {
        if (! $this->isTieredProgram()) {
            return null;
        }

        if (! $this->relationLoaded('tiers')) {
            $this->load('tiers');
        }

        if ($this->tiers->isEmpty()) {
            return null;
        }

        return $this->tiers
            ->sortBy('tier_order')
            ->map(fn ($tier) => $this->tierExitLabel($tier->tier_key) ?? $tier->tier_key)
            ->filter()
            ->implode(' → ');
    }

    /**
     * Duration column for academics / department lists.
     */
    public function listDurationLabel(): string
    {
        if ($this->isTieredProgram()) {
            if (! $this->relationLoaded('tiers')) {
                $this->load('tiers');
            }

            $months = $this->tiers->sortBy('tier_order')->pluck('cumulative_months')->filter()->values();
            if ($months->isNotEmpty()) {
                return $months->implode(' / ').' mo ladder';
            }
        }

        return $this->duration_months ? ((int) $this->duration_months).' months' : '-';
    }

    /**
     * Terms / semesters column for academics lists.
     */
    public function listTeachingPeriodsLabel(): string
    {
        if ($this->isTieredProgram()) {
            return $this->scheduledPeriods().' semesters (tiered)';
        }

        $terms = (int) ($this->semester_count ?: $this->termsPerYear());

        return $terms > 0 ? (string) $terms : '-';
    }

    public function nursingBlocks(): HasMany
    {
        return $this->hasMany(NursingBlock::class, 'program_id')->orderBy('block_order');
    }

    public function usesBlocks(): bool
    {
        return $this->curriculum_format === 'block' || (bool) $this->is_nursing_track;
    }

    public function termsPerYear(): int
    {
        if ($this->semester_count > 0) {
            return (int) $this->semester_count;
        }

        return match ($this->curriculum_format) {
            'semester' => 2,
            'trimester' => 3,
            default => 3,
        };
    }

    public function programYears(): int
    {
        return max(1, (int) ceil(((int) ($this->duration_months ?: 12)) / 12));
    }

    public function totalTeachingPeriods(): int
    {
        if ($this->usesBlocks()) {
            $blockTotal = $this->relationLoaded('nursingBlocks')
                ? $this->nursingBlocks->count()
                : $this->nursingBlocks()->count();

            return max(1, (int) ($blockTotal ?: $this->block_count ?: 1));
        }

        return $this->programYears() * $this->termsPerYear();
    }

    /**
     * How many years of study this programme actually runs. The record can be configured for fewer
     * months than the degree takes while the cohort is still pushed on to their final year, so the
     * four years on the calendar are the floor - that is what makes Year 2, 3 and 4 reachable.
     */
    public function studyYears(): int
    {
        if ($this->usesBlocks()) {
            return 1;
        }

        return max(4, $this->programYears());
    }

    /**
     * The teaching periods the curriculum, semester and timetable screens offer: every year the
     * students sit, not only the years the programme record currently counts.
     */
    public function scheduledPeriods(): int
    {
        if ($this->usesBlocks()) {
            return $this->totalTeachingPeriods();
        }

        if ($this->isTieredProgram()) {
            $max = $this->tierPlanner()->maxSemester($this);

            return max(1, $max);
        }

        return $this->studyYears() * $this->termsPerYear();
    }

    public function periodLabel(int $periodNumber): string
    {
        if ($this->usesBlocks()) {
            $block = $this->nursingBlocks->firstWhere('block_order', $periodNumber);

            return $block?->block_label ?? "Block {$periodNumber}";
        }

        if ($this->isTieredProgram()) {
            $tierKey = $this->tierPlanner()->tierForSemester($this, $periodNumber);
            $tierLabel = $tierKey
                ? (config('tich-program-tiers.tier_labels.'.$tierKey) ?? $tierKey)
                : null;

            return $tierLabel
                ? "Semester {$periodNumber} · {$tierLabel}"
                : "Semester {$periodNumber}";
        }

        $termsPerYear = $this->termsPerYear();
        $year = $this->yearOfPeriod($periodNumber);
        $termInYear = (($periodNumber - 1) % $termsPerYear) + 1;

        return "Year {$year} · Semester {$termInYear}";
    }

    /**
     * The year of study a teaching period falls in - block programmes keep every block in one group.
     */
    public function yearOfPeriod(int $periodNumber): int
    {
        if ($this->usesBlocks()) {
            return 1;
        }

        return max(1, (int) ceil($periodNumber / $this->termsPerYear()));
    }

    protected function tierPlanner(): ProgramTierPlannerService
    {
        return app(ProgramTierPlannerService::class);
    }

    public function coverImageUrl(): ?string
    {
        $path = $this->cover_image_path;

        if (! $path) {
            return null;
        }

        return \App\Support\PublicAsset::media($path);
    }

    public function feeStructure(): HasOne
    {
        return $this->hasOne(\App\Models\FeeStructure::class, 'program_id')
            ->where('is_active', 1)
            ->latest('effective_from');
    }
}
