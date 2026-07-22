<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Department;
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

    public function updateProgramFormat(User $user, AcademicProgram $program, array $data, ?Request $request = null): AcademicProgram
    {
        abort_unless($this->access->userCanAccessProgram($user, $program), 403);

        $old = $program->only(['curriculum_format', 'semester_count', 'block_count', 'is_nursing_track']);

        $program->update([
            'curriculum_format' => $data['curriculum_format'],
            'semester_count' => $data['semester_count'] ?? $program->semester_count,
            'block_count' => $data['block_count'] ?? $program->block_count,
            'is_nursing_track' => ($data['curriculum_format'] ?? '') === 'block' ? 1 : ($program->is_nursing_track ?? 0),
            'updated_at' => now(),
        ]);

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

    public function syncProgramUnits(User $user, AcademicProgram $program, array $mappings, ?Request $request = null): void
    {
        abort_unless($this->access->userCanAccessProgram($user, $program), 403);

        $activeUnitIds = Unit::query()
            ->where('department_id', $program->department_id)
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        ProgramUnit::query()->where('program_id', $program->id)->delete();

        foreach ($mappings as $index => $row) {
            if (empty($row['include'])) {
                continue;
            }

            $unitId = (int) ($row['unit_id'] ?? 0);

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
                'display_order' => (int) ($row['display_order'] ?? ($index + 1)),
                'priority' => (int) ($row['priority'] ?? ($row['display_order'] ?? ($index + 1))),
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

    public function initializeDepartment(User $user, array $data, ?Request $request = null): Department
    {
        abort_unless($this->access->canAccessAll($user), 403);

        $parent = Department::query()->where('dept_code', 'ACAD')->first();

        $department = Department::create([
            'dept_code' => strtoupper($data['dept_code']),
            'dept_name' => $data['dept_name'],
            'dept_category' => 'academic',
            'curriculum_profile' => $data['curriculum_profile'] ?? 'standard',
            'parent_dept_id' => $parent?->id,
            'campus_id' => $data['campus_id'] ?? null,
            'is_active' => false,
            'approval_status' => 'pending_ceo',
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->auditService->log(
            'academics.department.initialized',
            'departments',
            $department->id,
            null,
            $department->only(['dept_code', 'dept_name', 'curriculum_profile', 'approval_status']),
            'Academic department initialized — pending CEO sign-off',
            'success',
            $user->id,
            $request
        );

        return $department;
    }

    public function approveDepartmentCeo(User $user, Department $department, ?Request $request = null): Department
    {
        abort_unless($this->access->canApproveCeo($user), 403);
        abort_unless($department->approval_status === 'pending_ceo', 422);

        $department->update([
            'is_active' => true,
            'approval_status' => 'active',
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.department.ceo_approved',
            'departments',
            $department->id,
            ['approval_status' => 'pending_ceo'],
            ['approval_status' => 'active', 'is_active' => true],
            'Academic department activated by CEO',
            'success',
            $user->id,
            $request
        );

        return $department->fresh();
    }

    public function updateDepartmentProfile(User $user, Department $department, array $data, ?Request $request = null): Department
    {
        abort_unless($this->access->userCanAccessDepartment($user, $department), 403);

        $department->update([
            'curriculum_profile' => $data['curriculum_profile'],
            'updated_at' => now(),
        ]);

        return $department->fresh();
    }
}
