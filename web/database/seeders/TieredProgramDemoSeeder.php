<?php

namespace Database\Seeders;

use App\Models\AcademicProgram;
use App\Models\FeeStructure;
use App\Models\ProgramTier;
use App\Models\ProgramUnit;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Models\User;
use App\Services\Academics\ProgramTierPlannerService;
use App\Services\Academics\ProgramTierService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Full institutional 3-tier demo: Certificate (8 units) → Diploma (+9) → Advanced Diploma (+14).
 *
 * Run: php artisan db:seed --class=TieredProgramDemoSeeder
 *
 * Change PROGRAM_CODE below to reuse an existing ladder (e.g. TDMAAQ) instead of TIER-DEMO.
 */
class TieredProgramDemoSeeder extends Seeder
{
    public const PROGRAM_CODE = 'TIER-DEMO';

    /** @var array<string, int> tier_key => unit count in that tier's semesters */
    private const TIER_UNIT_COUNTS = [
        'certificate' => 8,
        'diploma' => 9,
        'advanced_diploma' => 14,
    ];

    /** @var array<string, int> tier_key => total semester fee (KES) */
    private const TIER_FEES = [
        'certificate' => 48000,
        'diploma' => 92000,
        'advanced_diploma' => 138000,
    ];

    public function run(): void
    {
        if (! Schema::hasTable('program_tiers') || ! Schema::hasColumn('academic_programs', 'is_tiered')) {
            $this->command?->error('Run tiered programme migration first (2026_11_02_120000_add_tiered_program_support).');

            return;
        }

        $departmentId = $this->resolveLearningDepartmentId();
        if (! $departmentId) {
            $this->command?->error('No learning department found. Run ProgramsSeeder / DepartmentModulesSeeder first.');

            return;
        }

        $actors = $this->resolveTeachingStaff($departmentId);
        if ($actors['lecturers']->isEmpty()) {
            $this->command?->error('No teaching staff found. Run LecturerSeeder or DatabaseSeeder first.');

            return;
        }

        $yearId = $this->ensureAcademicYearWithSemesters();
        $campusId = (int) (DB::table('campuses')->where('is_active', 1)->value('id') ?? 0);

        $program = $this->ensureProgram($departmentId, $actors['registrar']);
        $this->syncTierDefinitions($program);
        $program->refresh()->load('tiers');

        $planner = app(ProgramTierPlannerService::class);
        $plan = $planner->buildPlan($program, $program->tiers->map(fn (ProgramTier $t) => [
            'tier_key' => $t->tier_key,
            'tier_order' => $t->tier_order,
            'cumulative_months' => $t->cumulative_months,
        ])->all());

        $semesterUnitCounts = $this->distributeUnitsAcrossSemesters($plan);
        $units = $this->seedUnits($program, $departmentId, $semesterUnitCounts, $plan, $actors);
        $curriculumVersionId = $this->seedPublishedCurriculum($program, $yearId, $units, $plan, $actors);
        $this->seedProgramUnitLinks($program, $units, $plan);
        $this->seedTierFees($program, $yearId, $actors['registrar']);
        $this->seedLecturerAllocations($program, $units, $actors['lecturers'], $yearId, $campusId);

        $this->command?->info(sprintf(
            'Tiered demo ready: %s (#%d) — %d units, published intake #%d, %d lecturers allocated.',
            $program->program_code,
            $program->id,
            $units->count(),
            $curriculumVersionId,
            $actors['lecturers']->count(),
        ));
        $this->command?->info('Public: /programs/'.urlencode($program->program_code));
        $this->command?->info('Apply: /apply?program='.$program->program_code.'&tier=certificate');
    }

    private function resolveLearningDepartmentId(): ?int
    {
        return DB::table('departments')
            ->where(function ($q) {
                $q->where('dept_code', 'ICT')
                    ->orWhere('dept_code', 'CHS')
                    ->orWhere(function ($inner) {
                        $inner->where('dept_category', 'academic')
                            ->whereNotNull('parent_dept_id')
                            ->where('is_active', 1);
                    });
            })
            ->orderByRaw("CASE WHEN dept_code = 'ICT' THEN 0 WHEN dept_code = 'CHS' THEN 1 ELSE 2 END")
            ->value('id');
    }

    /**
     * @return array{registrar: ?Staff, lecturers: \Illuminate\Support\Collection<int, Staff>}
     */
    private function resolveTeachingStaff(int $departmentId): array
    {
        $registrarUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('role_name', 'Academic Registrar'))
            ->first();

        $lecturers = Staff::query()
            ->where('is_teaching_staff', 1)
            ->where('employment_status', 'active')
            ->orderBy('id')
            ->limit(6)
            ->get();

        if ($lecturers->count() < 2) {
            $this->call(LecturerSeeder::class);
            $lecturers = Staff::query()
                ->where('is_teaching_staff', 1)
                ->where('employment_status', 'active')
                ->orderBy('id')
                ->limit(6)
                ->get();
        }

        return [
            'registrar' => $registrarUser?->staff,
            'lecturers' => $lecturers,
        ];
    }

    private function ensureAcademicYearWithSemesters(): int
    {
        $yearId = DB::table('academic_years')->where('is_current', 1)->value('id')
            ?? DB::table('academic_years')->orderByDesc('id')->value('id');

        if (! $yearId) {
            $yearId = DB::table('academic_years')->insertGetId([
                'year_label' => '2026/2027',
                'start_date' => '2026-08-01',
                'end_date' => '2028-07-31',
                'is_current' => 1,
                'created_at' => now(),
            ]);
        }

        for ($sem = 1; $sem <= 8; $sem++) {
            $start = match ($sem) {
                1 => '2026-08-10',
                2 => '2027-01-12',
                3 => '2027-05-15',
                4 => '2027-09-01',
                5 => '2028-01-08',
                6 => '2028-05-10',
                7 => '2028-09-05',
                default => '2029-01-10',
            };
            $end = date('Y-m-d', strtotime($start.' +3 months'));

            DB::table('semesters')->updateOrInsert(
                ['academic_year_id' => $yearId, 'semester_number' => $sem],
                [
                    'semester_label' => 'Semester '.$sem,
                    'intake_month' => 'August',
                    'start_date' => $start,
                    'end_date' => $end,
                    'registration_open_date' => $start,
                    'registration_close_date' => $end,
                    'is_current' => $sem === 1 ? 1 : 0,
                    'created_at' => now(),
                ]
            );
        }

        return (int) $yearId;
    }

    private function ensureProgram(int $departmentId, ?Staff $registrar): AcademicProgram
    {
        $cover = AcademicProgram::query()->whereNotNull('cover_image_path')->value('cover_image_path');

        $program = AcademicProgram::query()->firstOrNew(['program_code' => self::PROGRAM_CODE]);

        $program->fill([
            'program_name' => $program->exists ? $program->program_name : 'Technology & Digital Media (Tiered Demo)',
            'program_type' => 'tiered',
            'regulatory_body' => null,
            'curriculum_format' => 'semester',
            'is_tiered' => true,
            'tier_semester_months' => 3,
            'department_id' => $departmentId,
            'duration_months' => 24,
            'semester_count' => 8,
            'status' => 'active',
            'is_featured_on_homepage' => true,
            'homepage_display_order' => 2,
            'homepage_tagline' => '<p>Build digital skills step by step: exit with a <strong>Certificate</strong>, return later for <strong>Diploma</strong> or the full <strong>Advanced Diploma</strong> on one programme code.</p>',
            'entry_requirements' => '<p>KCSE mean grade D+ or equivalent. Same entry criteria for every tier; you choose how far you study in one application.</p>',
            'min_attendance_pct' => 75,
            'theory_pass_mark' => 40,
            'approved_by_ceo_at' => now()->subMonth(),
            'created_by' => $registrar?->user_id,
        ]);

        if ($cover && ! $program->cover_image_path) {
            $program->cover_image_path = $cover;
        }

        if (! $program->exists) {
            $program->created_at = now();
        }

        $program->save();

        return $program->fresh();
    }

    private function syncTierDefinitions(AcademicProgram $program): void
    {
        $labels = config('tich-program-tiers.tier_labels', []);
        $payload = [
            'is_tiered' => true,
            'tier_semester_months' => 3,
            'tiers' => [
                [
                    'tier_key' => 'certificate',
                    'tier_order' => 1,
                    'cumulative_months' => 6,
                    'learning_outcomes' => '<p>Foundations in digital literacy, communication, and introductory production skills.</p>',
                    'marketing_summary' => 'Six-month Certificate exit with job-ready basics.',
                ],
                [
                    'tier_key' => 'diploma',
                    'tier_order' => 2,
                    'cumulative_months' => 12,
                    'learning_outcomes' => '<p>Intermediate studio work, client projects, and team-based production.</p>',
                    'marketing_summary' => 'Twelve-month Diploma including Certificate content plus applied modules.',
                ],
                [
                    'tier_key' => 'advanced_diploma',
                    'tier_order' => 3,
                    'cumulative_months' => 24,
                    'learning_outcomes' => '<p>Advanced portfolio, entrepreneurship, and capstone industry placement.</p>',
                    'marketing_summary' => 'Full twenty-four month Advanced Diploma ladder.',
                ],
            ],
        ];

        app(ProgramTierService::class)->syncTiers($program, $payload);

        foreach ($payload['tiers'] as $row) {
            ProgramTier::query()
                ->where('program_id', $program->id)
                ->where('tier_key', $row['tier_key'])
                ->update([
                    'learning_outcomes' => $row['learning_outcomes'],
                    'marketing_summary' => $row['marketing_summary'],
                ]);
        }
    }

    /**
     * @param  list<array{tier_key: string, semester_from: int, semester_to: int}>  $plan
     * @return array<int, int> semester_number => unit_count
     */
    private function distributeUnitsAcrossSemesters(array $plan): array
    {
        $byTier = self::TIER_UNIT_COUNTS;
        $distribution = [];

        foreach ($plan as $row) {
            $tierKey = $row['tier_key'];
            $total = $byTier[$tierKey] ?? 0;
            $from = (int) $row['semester_from'];
            $to = (int) $row['semester_to'];
            $semesterCount = max(1, $to - $from + 1);
            $base = intdiv($total, $semesterCount);
            $remainder = $total % $semesterCount;

            for ($sem = $from; $sem <= $to; $sem++) {
                $extra = ($sem - $from) < $remainder ? 1 : 0;
                $distribution[$sem] = ($distribution[$sem] ?? 0) + $base + $extra;
            }
        }

        return $distribution;
    }

    /**
     * @param  array<int, int>  $semesterUnitCounts
     * @param  list<array{tier_key: string, semester_from: int, semester_to: int, label: string}>  $plan
     * @param  array{registrar: ?Staff, lecturers: \Illuminate\Support\Collection<int, Staff>}  $actors
     * @return \Illuminate\Support\Collection<int, Unit>
     */
    private function seedUnits(
        AcademicProgram $program,
        int $departmentId,
        array $semesterUnitCounts,
        array $plan,
        array $actors,
    ) {
        $hod = $actors['lecturers']->first();
        $registrar = $actors['registrar'] ?? $hod;
        $tierBySemester = [];
        foreach ($plan as $row) {
            for ($s = $row['semester_from']; $s <= $row['semester_to']; $s++) {
                $tierBySemester[$s] = $row['tier_key'];
            }
        }

        $labels = config('tich-program-tiers.tier_labels', []);
        $units = collect();
        $counter = 1;

        foreach ($semesterUnitCounts as $semester => $count) {
            $tierKey = $tierBySemester[$semester] ?? 'certificate';
            $tierLabel = $labels[$tierKey] ?? $tierKey;

            for ($i = 1; $i <= $count; $i++) {
                $code = sprintf('%s-U%02d', self::PROGRAM_CODE, $counter);
                $name = sprintf('%s — %s (Sem %d)', $tierLabel, $this->unitTopicName($counter), $semester);

                $unit = Unit::query()->updateOrCreate(
                    ['unit_code' => $code],
                    [
                        'unit_name' => $name,
                        'description' => 'Demo unit for tiered programme showcase.',
                        'department_id' => $departmentId,
                        'program_id' => $program->id,
                        'semester' => $semester,
                        'credit_hours' => 3,
                        'contact_hours' => 45,
                        'total_learning_hours' => 60,
                        'display_priority' => $counter,
                        'is_core' => true,
                        'is_practical' => $counter % 3 === 0,
                        'assessment_weight_attendance_pct' => 10,
                        'assessment_weight_cat_pct' => 30,
                        'assessment_weight_practical_pct' => 10,
                        'assessment_weight_exam_pct' => 50,
                        'status' => 'active',
                        'submitted_at' => now()->subMonths(2),
                        'submitted_by' => $hod?->id,
                        'registrar_approved_at' => now()->subMonths(2)->addDay(),
                        'registrar_approved_by' => $registrar?->id,
                        'created_by' => $hod?->id,
                        'updated_at' => now(),
                    ]
                );

                $unit->semester = $semester;
                $units->push($unit);
                $counter++;
            }
        }

        Unit::query()
            ->where('program_id', $program->id)
            ->whereNotIn('id', $units->pluck('id'))
            ->delete();

        return $units;
    }

    private function unitTopicName(int $index): string
    {
        $topics = [
            'Digital Foundations', 'Visual Communication', 'Content Creation', 'Web Basics',
            'Media Ethics', 'Audio Production', 'Video Editing', 'Graphic Design',
            'Project Management', 'Motion Graphics', 'UX Fundamentals', 'Live Streaming',
            'Brand Strategy', 'Data for Creatives', '3D Introduction', 'Photography',
            'Podcasting', 'Social Campaigns', 'Client Briefs', 'Studio Practice',
            'Advanced Editing', 'Portfolio Development', 'Entrepreneurship', 'Industry Placement Prep',
            'Capstone Planning', 'Research Methods', 'Leadership in Media', 'Innovation Lab',
            'Professional Practice', 'Exhibition & Review', 'Enterprise Project',
        ];

        return $topics[$index - 1] ?? 'Specialist Topic '.$index;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Unit>  $units
     * @param  list<array{tier_key: string, semester_from: int, semester_to: int}>  $plan
     */
    private function seedPublishedCurriculum(
        AcademicProgram $program,
        int $yearId,
        $units,
        array $plan,
        array $actors,
    ): int {
        $hod = $actors['lecturers']->first();
        $registrar = $actors['registrar'] ?? $hod;

        $versionId = DB::table('curriculum_versions')
            ->where('program_id', $program->id)
            ->where('intake_year', 2026)
            ->where('intake_month', 8)
            ->value('id');

        $payload = [
            'academic_year_id' => $yearId,
            'version_label' => self::PROGRAM_CODE.' Aug 2026 tiered intake',
            'version_number' => 1,
            'curriculum_format' => 'semester',
            'intake_year' => 2026,
            'intake_month' => 8,
            'status' => 'published',
            'notes' => 'Demo published curriculum for institutional tiered programme.',
            'created_by' => $hod?->id,
            'submitted_at' => now()->subMonths(2),
            'registrar_approved_at' => now()->subMonths(2)->addDay(),
            'registrar_approved_by' => $registrar?->id,
            'published_at' => now()->subMonth(),
            'published_by' => $registrar?->user_id,
            'updated_at' => now(),
        ];

        if ($versionId) {
            DB::table('curriculum_versions')->where('id', $versionId)->update($payload);
        } else {
            $versionId = DB::table('curriculum_versions')->insertGetId(array_merge($payload, [
                'program_id' => $program->id,
                'created_at' => now(),
            ]));
        }

        DB::table('curriculum_version_units')->where('curriculum_version_id', $versionId)->delete();

        if (Schema::hasTable('curriculum_version_periods')) {
            DB::table('curriculum_version_periods')->where('curriculum_version_id', $versionId)->delete();
            $periodRows = [];
            foreach ($plan as $row) {
                for ($sem = $row['semester_from']; $sem <= $row['semester_to']; $sem++) {
                    if (isset($periodRows[$sem])) {
                        continue;
                    }
                    $start = sprintf('2026-%02d-01', min(12, 6 + (($sem - 1) % 6)));
                    $periodRows[$sem] = [
                        'curriculum_version_id' => $versionId,
                        'semester' => $sem,
                        'start_date' => $start,
                        'end_date' => date('Y-m-d', strtotime($start.' +3 months')),
                        'learning_start_date' => $start,
                        'learning_end_date' => date('Y-m-d', strtotime($start.' +10 weeks')),
                        'exam_start_date' => date('Y-m-d', strtotime($start.' +11 weeks')),
                        'exam_end_date' => date('Y-m-d', strtotime($start.' +3 months')),
                    ];
                }
            }
            DB::table('curriculum_version_periods')->insert(array_values($periodRows));
        }

        $order = 1;
        foreach ($units->sortBy('semester') as $unit) {
            DB::table('curriculum_version_units')->insert([
                'curriculum_version_id' => $versionId,
                'unit_id' => $unit->id,
                'semester' => $unit->semester,
                'is_compulsory' => 1,
                'display_order' => $order,
                'priority' => $order,
                'credit_hours' => $unit->credit_hours,
                'contact_hours' => $unit->contact_hours,
                'total_learning_hours' => $unit->total_learning_hours,
            ]);
            $order++;
        }

        return (int) $versionId;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Unit>  $units
     * @param  list<array{tier_key: string, semester_from: int, semester_to: int}>  $plan
     */
    private function seedProgramUnitLinks(AcademicProgram $program, $units, array $plan): void
    {
        $planner = app(ProgramTierPlannerService::class);
        ProgramUnit::query()->where('program_id', $program->id)->delete();

        $order = 1;
        foreach ($units->sortBy('semester') as $unit) {
            $semester = (int) $unit->semester;
            ProgramUnit::create([
                'program_id' => $program->id,
                'unit_id' => $unit->id,
                'semester' => $semester,
                'program_tier' => $planner->tierForSemester($program->fresh(['tiers']), $semester),
                'is_compulsory' => 1,
                'display_order' => $order,
                'priority' => $order,
                'contact_hours' => $unit->contact_hours,
                'total_learning_hours' => $unit->total_learning_hours,
                'is_active' => 1,
            ]);
            $order++;
        }
    }

    private function seedTierFees(AcademicProgram $program, int $yearId, ?Staff $registrar): void
    {
        FeeStructure::query()
            ->where('program_id', $program->id)
            ->where('academic_year_id', $yearId)
            ->delete();

        foreach (self::TIER_FEES as $tierKey => $amount) {
            FeeStructure::query()->updateOrCreate(
                [
                    'program_id' => $program->id,
                    'academic_year_id' => $yearId,
                    'program_tier' => $tierKey,
                ],
                [
                    'effective_from' => now()->toDateString(),
                    'is_active' => true,
                    'is_approved' => true,
                    'approved_by' => $registrar?->id,
                    'approved_at' => now(),
                    'tuition_fee' => round($amount * 0.8, 2),
                    'total_semester_fee' => $amount,
                    'application_fee' => 1000,
                    'qa_annual_fee' => 1000,
                    'graduation_fee' => 4000,
                ]
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Unit>  $units
     * @param  \Illuminate\Support\Collection<int, Staff>  $lecturers
     */
    private function seedLecturerAllocations(
        AcademicProgram $program,
        $units,
        $lecturers,
        int $yearId,
        int $campusId,
    ): void {
        $semesterIds = DB::table('semesters')
            ->where('academic_year_id', $yearId)
            ->pluck('id', 'semester_number');

        $lecturerList = $lecturers->values();
        $index = 0;

        foreach ($units as $unit) {
            $staff = $lecturerList[$index % $lecturerList->count()];
            $index++;
            $semesterId = (int) ($semesterIds[$unit->semester] ?? $semesterIds->first());

            $attributes = [
                'unit_id' => $unit->id,
                'staff_id' => $staff->id,
                'semester_id' => $semesterId,
            ];

            $values = [
                'campus_id' => $campusId ?: null,
                'contact_hours_assigned' => max(3, (int) round(((int) $unit->contact_hours) / 15)),
                'is_coordinator' => $index % 4 === 0,
                'is_active' => 1,
            ];

            if (Schema::hasColumn('unit_allocations', 'program_id')) {
                $values['program_id'] = $program->id;
            }
            if (Schema::hasColumn('unit_allocations', 'status')) {
                $values['status'] = UnitAllocation::STATUS_APPROVED;
                $values['approved_at'] = now();
                $values['approved_by'] = $staff->user_id;
            }

            UnitAllocation::query()->updateOrCreate($attributes, $values);
        }
    }
}
