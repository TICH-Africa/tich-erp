<?php

namespace App\Services;

use App\Models\AcademicProgram;
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
}
