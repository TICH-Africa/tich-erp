<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Applicant;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\NursingBlock;
use App\Models\ProgramUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ProgramCurriculumService
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected AuditService $auditService,
        protected RBACService $rbacService,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function curriculumFormats(): array
    {
        return [
            'modular' => 'Modular (NITA / CDACC competency)',
            'semester' => 'Semester (credit matrix)',
            'trimester' => 'Trimester (3 terms per year)',
            'block' => 'Block (nursing clinical blocks)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function curriculumProfiles(): array
    {
        return [
            'standard' => 'Standard academic',
            'chd' => 'Community Health & Development (CDACC/TVET)',
            'nursing' => 'Nursing (Nursing Council blocks)',
            'vocational' => 'Vocational / NITA artisan',
            'ict' => 'ICT (semester credit tracks)',
        ];
    }

    public function updateProgramFormat(User $user, Department $hub, AcademicProgram $program, array $data, ?Request $request = null): AcademicProgram
    {
        $scopeIds = $hub->academicsScopeDepartmentIds();
        abort_unless(in_array((int) $program->department_id, $scopeIds, true), 403);

        $old = $program->only(['curriculum_format', 'semester_count', 'block_count', 'is_nursing_track', 'duration_months']);

        $blockCount = (int) ($data['block_count'] ?? $program->block_count ?? 0);

        $program->update([
            'curriculum_format' => $data['curriculum_format'],
            'semester_count' => (int) ($data['semester_count'] ?? $program->semester_count ?? 3),
            'block_count' => $blockCount,
            'duration_months' => (int) ($data['duration_months'] ?? $program->duration_months ?? 12),
            'is_nursing_track' => ($data['curriculum_format'] ?? '') === 'block' ? 1 : ($program->is_nursing_track ?? 0),
            'updated_at' => now(),
        ]);

        if (($data['curriculum_format'] ?? '') === 'block' && $blockCount > 0) {
            $this->syncNursingBlocks($program, $blockCount);
        }

        $this->auditService->log(
            'academics.program.curriculum_format_updated',
            'academic_programs',
            $program->id,
            $old,
            $program->only(['curriculum_format', 'semester_count', 'block_count']),
            'Program curriculum format updated',
            'success',
            $user->id,
            $request
        );

        return $program->fresh();
    }

    public function syncProgramUnits(User $user, Department $hub, AcademicProgram $program, array $mappings, ?Request $request = null): void
    {
        $scopeIds = $hub->academicsScopeDepartmentIds();
        abort_unless(in_array((int) $program->department_id, $scopeIds, true), 403);

        $activeUnitIds = Unit::query()
            ->where('status', 'active')
            ->where(function ($builder) use ($scopeIds) {
                $builder->whereIn('department_id', $scopeIds)
                    ->orWhereHas('program', fn ($q) => $q->whereIn('department_id', $scopeIds));
            })
            ->pluck('id')
            ->all();

        ProgramUnit::query()->where('program_id', $program->id)->delete();

        $included = [];

        foreach ($mappings as $index => $row) {
            if (empty($row['include'])) {
                continue;
            }

            $unitId = (int) ($row['unit_id'] ?? 0);

            if ($unitId === 0) {
                continue;
            }

            $included[$unitId] = array_merge($row, [
                'display_order' => (int) ($row['display_order'] ?? ($index + 1)),
                'priority' => (int) ($row['priority'] ?? ($row['display_order'] ?? ($index + 1))),
            ]);
        }

        foreach ($included as $unitId => $row) {
            if (! in_array($unitId, $activeUnitIds, true)) {
                continue;
            }

            $unit = Unit::query()->find($unitId);

            ProgramUnit::create([
                'program_id' => $program->id,
                'unit_id' => $unitId,
                'semester' => (int) ($row['semester'] ?? 1),
                'block_id' => $row['block_id'] ?? null,
                'is_compulsory' => ! empty($row['is_compulsory']) ? 1 : 0,
                'display_order' => (int) ($row['display_order'] ?? 1),
                'priority' => (int) ($row['priority'] ?? 1),
                'contact_hours' => (int) ($row['contact_hours'] ?? $unit?->contact_hours ?? 0),
                'total_learning_hours' => (int) ($row['total_learning_hours'] ?? $unit?->total_learning_hours ?? 0),
                'is_active' => 1,
            ]);
        }

        $this->auditService->log(
            'academics.program.units_synced',
            'academic_programs',
            $program->id,
            null,
            ['mapped_units' => count($mappings)],
            'Program unit mapping updated',
            'success',
            $user->id,
            $request
        );
    }

    /**
     * @return Collection<int, ProgramUnit>
     */
    public function mappedUnits(AcademicProgram $program): Collection
    {
        return ProgramUnit::query()
            ->with(['unit', 'block'])
            ->where('program_id', $program->id)
            ->orderBy('display_order')
            ->orderBy('priority')
            ->get();
    }

    private function syncNursingBlocks(AcademicProgram $program, int $count): void
    {
        $existing = $program->nursingBlocks()->orderBy('block_order')->get();

        for ($order = 1; $order <= $count; $order++) {
            if ($existing->firstWhere('block_order', $order)) {
                continue;
            }

            NursingBlock::create([
                'program_id' => $program->id,
                'block_label' => "Block {$order}",
                'block_order' => $order,
                'duration_months' => 4,
                'is_active' => 1,
            ]);
        }
    }

    public function updateDepartmentProfile(User $user, Department $department, array $data, ?Request $request = null): Department
    {
        $department->update([
            'curriculum_profile' => $data['curriculum_profile'],
            'updated_at' => now(),
        ]);

        return $department->fresh();
    }

    /**
     * @return array<string, string>
     */
    public static function curriculumSections(): array
    {
        return [
            'structure' => 'Programme structure',
            'intakes' => 'Intakes',
            'catalog' => 'Unit catalog',
            'semesters' => 'Semester units',
            'applications' => 'Applications',
            'workflow' => 'Intake workflow',
        ];
    }

    public function resolveSection(Request $request): string
    {
        $section = $request->string('section')->toString() ?: 'structure';

        return array_key_exists($section, self::curriculumSections()) ? $section : 'structure';
    }

    /**
     * @return list<array{type: 'link'|'heading', label: string, route?: string, params?: array<string, mixed>, section?: string, target_id?: int}>
     */
    public function curriculumSidebarNavigation(
        Department $hub,
        ?Department $learningDepartment,
        AcademicProgram $program,
        ?CurriculumVersion $selectedIntake,
        User $user,
    ): array {
        $programParams = array_filter([
            'department' => $hub->id,
            'program' => $program->id,
            'learning_department' => $learningDepartment?->id,
            'intake' => $selectedIntake?->id,
        ]);

        $listParams = array_filter([
            'department' => $hub->id,
            'learning_department' => $learningDepartment?->id,
        ]);

        $items = [
            [
                'type' => 'link',
                'label' => 'All programmes',
                'route' => 'departments.academics.programs.index',
                'params' => $listParams,
            ],
            ['type' => 'heading', 'label' => $program->program_code],
        ];

        $requiresIntake = ['semesters', 'applications', 'workflow'];
        $canViewApplications = $this->rbacService->hasPermission($user, 'admissions.read');

        foreach (self::curriculumSections() as $key => $label) {
            if ($key === 'applications' && ! $canViewApplications) {
                continue;
            }

            if (in_array($key, $requiresIntake, true) && ! $selectedIntake) {
                continue;
            }

            $items[] = [
                'type' => 'link',
                'label' => $label,
                'route' => 'departments.academics.programs.curriculum',
                'params' => array_merge($programParams, ['section' => $key]),
                'section' => $key,
            ];
        }

        if ($learningDepartment) {
            $items[] = ['type' => 'heading', 'label' => 'Department'];
            $items[] = [
                'type' => 'link',
                'label' => 'Overview',
                'route' => 'departments.show',
                'params' => ['department' => $learningDepartment->getRouteKey()],
                'target_id' => $learningDepartment->id,
                'section' => 'overview',
            ];
        }

        $items[] = ['type' => 'heading', 'label' => 'Account'];
        $items[] = [
            'type' => 'link',
            'label' => 'Main dashboard',
            'route' => 'dashboard',
            'params' => [],
        ];

        return $items;
    }

    /**
     * @return Collection<int, Applicant>
     */
    public function applicationsForIntake(AcademicProgram $program, ?CurriculumVersion $intake): Collection
    {
        $query = Applicant::query()
            ->with(['preferredCampus', 'program.department', 'handlingDepartment'])
            ->where('program_id', $program->id)
            ->orderByDesc('created_at');

        if ($intake?->intake_year && $intake?->intake_month) {
            $query->where('intake_year', $intake->intake_year)
                ->where('intake_month', $intake->intake_month);
        }

        return $query->get();
    }
}
