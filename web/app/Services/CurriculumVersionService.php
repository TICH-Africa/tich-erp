<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionPeriod;
use App\Models\CurriculumVersionUnit;
use App\Models\Department;
use App\Models\ProgramUnit;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CurriculumVersionService
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected AuditService $auditService,
    ) {}

    /**
     * @return list<string>
     */
    public static function mappableUnitStatuses(): array
    {
        return ['active', 'draft', 'pending_registry'];
    }

    public function createDraft(User $user, Department $hub, AcademicProgram $program, array $data, ?Request $request = null): CurriculumVersion
    {
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $program), 403);

        $intakeYear = (int) ($data['intake_year'] ?? 0);
        $intakeMonth = (int) ($data['intake_month'] ?? 0);

        if ($intakeYear < 2000 || $intakeMonth < 1 || $intakeMonth > 12) {
            throw ValidationException::withMessages([
                'intake_year' => 'Intake year and month are required.',
                'intake_month' => 'Intake year and month are required.',
            ]);
        }

        $duplicate = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->where('intake_year', $intakeYear)
            ->where('intake_month', $intakeMonth)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'intake_year' => 'An intake already exists for this programme in that month and year.',
            ]);
        }

        $latestNumber = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->max('version_number') ?? 0;

        $monthLabel = date('M', mktime(0, 0, 0, $intakeMonth, 1));
        $defaultLabel = "{$monthLabel} {$intakeYear} intake";

        $version = CurriculumVersion::create([
            'program_id' => $program->id,
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'intake_year' => $intakeYear,
            'intake_month' => $intakeMonth,
            'version_label' => $data['version_label'] ?? $defaultLabel,
            'version_number' => $latestNumber + 1,
            'curriculum_format' => $data['curriculum_format'] ?? $program->curriculum_format ?? 'trimester',
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $copyFromId = (int) ($data['copy_from_version_id'] ?? 0);

        if ($copyFromId > 0) {
            $source = CurriculumVersion::query()
                ->where('program_id', $program->id)
                ->where('id', $copyFromId)
                ->first();

            if ($source) {
                $this->copyUnits($source, $version);
                $this->copyPeriods($source, $version);
            }
        } elseif (! empty($data['copy_from_program_template'])) {
            $this->copyFromProgramTemplate($program, $version);
        }

        $this->auditService->log(
            'academics.curriculum_version.created',
            'curriculum_versions',
            $version->id,
            null,
            $version->only(['program_id', 'version_label', 'intake_year', 'intake_month', 'status']),
            'Programme intake draft created',
            'success',
            $user->id,
            $request
        );

        return $version->fresh('items.unit');
    }

    /**
     * @param  array<int, array<string, mixed>>  $mappings
     */
    public function syncVersionUnits(
        User $user,
        Department $hub,
        CurriculumVersion $version,
        array $mappings,
        ?Request $request = null
    ): void {
        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);
        abort_unless($version->status === 'draft', 422, 'Only draft intakes can be edited.');

        $program = $version->program;
        $scopeIds = $hub->academicsScopeDepartmentIds();

        $mappableUnitIds = Unit::query()
            ->whereIn('status', self::mappableUnitStatuses())
            ->where(function ($builder) use ($scopeIds) {
                $builder->whereIn('department_id', $scopeIds)
                    ->orWhereHas('program', fn ($q) => $q->whereIn('department_id', $scopeIds));
            })
            ->pluck('id')
            ->all();

        CurriculumVersionUnit::query()->where('curriculum_version_id', $version->id)->delete();

        $included = [];

        foreach ($mappings as $index => $row) {
            if (empty($row['include'])) {
                continue;
            }

            $unitId = (int) ($row['unit_id'] ?? 0);

            if ($unitId === 0 || ! in_array($unitId, $mappableUnitIds, true)) {
                continue;
            }

            $included[$unitId] = array_merge($row, [
                'display_order' => (int) ($row['display_order'] ?? ($index + 1)),
                'priority' => (int) ($row['priority'] ?? ($row['display_order'] ?? ($index + 1))),
            ]);
        }

        foreach ($included as $unitId => $row) {
            $unit = Unit::query()->find($unitId);

            CurriculumVersionUnit::create([
                'curriculum_version_id' => $version->id,
                'unit_id' => $unitId,
                'semester' => (int) ($row['semester'] ?? 1),
                'block_id' => $row['block_id'] ?? null,
                'is_compulsory' => ! empty($row['is_compulsory']) ? 1 : 0,
                'display_order' => (int) ($row['display_order'] ?? 1),
                'priority' => (int) ($row['priority'] ?? 1),
                'credit_hours' => $unit?->credit_hours ?? 0,
                'contact_hours' => (int) ($row['contact_hours'] ?? $unit?->contact_hours ?? 0),
                'total_learning_hours' => (int) ($row['total_learning_hours'] ?? $unit?->total_learning_hours ?? 0),
            ]);
        }

        $this->auditService->log(
            'academics.curriculum_version.units_synced',
            'curriculum_versions',
            $version->id,
            null,
            ['mapped_units' => count($included), 'program_id' => $program->id],
            'Intake unit mapping updated',
            'success',
            $user->id,
            $request
        );
    }

    public function addUnitToPeriod(
        User $user,
        Department $hub,
        CurriculumVersion $version,
        int $unitId,
        int $semester,
        ?int $blockId = null,
        ?Request $request = null
    ): CurriculumVersionUnit {
        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);
        abort_unless($version->status === 'draft', 422);

        $unit = Unit::query()
            ->where('id', $unitId)
            ->whereIn('status', self::mappableUnitStatuses())
            ->firstOrFail();

        if (CurriculumVersionUnit::query()->where('curriculum_version_id', $version->id)->where('unit_id', $unitId)->exists()) {
            throw ValidationException::withMessages([
                'unit_id' => 'This unit is already assigned in this intake.',
            ]);
        }

        $periodQuery = CurriculumVersionUnit::query()->where('curriculum_version_id', $version->id);
        if ($blockId) {
            $periodQuery->where('block_id', $blockId);
        } else {
            $periodQuery->where('semester', $semester);
        }
        $nextOrder = ((int) $periodQuery->max('display_order')) + 1;

        $mapping = CurriculumVersionUnit::create([
            'curriculum_version_id' => $version->id,
            'unit_id' => $unitId,
            'semester' => $semester,
            'block_id' => $blockId,
            'is_compulsory' => $unit->is_core ?? true,
            'display_order' => $nextOrder,
            'priority' => $nextOrder,
            'credit_hours' => $unit->credit_hours ?? 0,
            'contact_hours' => $unit->contact_hours ?? 0,
            'total_learning_hours' => $unit->total_learning_hours ?? 0,
        ]);

        $this->auditService->log(
            'academics.curriculum_version.unit_added',
            'curriculum_versions',
            $version->id,
            null,
            ['unit_id' => $unitId, 'semester' => $semester],
            'Unit added to intake semester',
            'success',
            $user->id,
            $request
        );

        return $mapping;
    }

    /**
     * @param  list<int>  $unitIds
     * @return list<CurriculumVersionUnit>
     */
    public function addUnitsToPeriod(
        User $user,
        Department $hub,
        CurriculumVersion $version,
        array $unitIds,
        int $semester,
        ?int $blockId = null,
        ?Request $request = null
    ): array {
        $added = [];

        foreach (array_values(array_unique(array_map('intval', $unitIds))) as $unitId) {
            if ($unitId <= 0) {
                continue;
            }

            $added[] = $this->addUnitToPeriod($user, $hub, $version, $unitId, $semester, $blockId, $request);
        }

        if ($added === []) {
            throw ValidationException::withMessages([
                'unit_ids' => 'Select at least one unit to assign.',
            ]);
        }

        return $added;
    }

    public function reopenDraft(User $user, Department $hub, CurriculumVersion $version, ?Request $request = null): CurriculumVersion
    {
        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);

        if ($version->status === 'draft') {
            return $version;
        }

        if (! in_array($version->status, ['pending_registry', 'pending_ceo', 'published'], true)) {
            abort(422, 'This intake cannot be reopened for editing.');
        }

        $previousStatus = $version->status;

        $version->update([
            'status' => 'draft',
            'submitted_at' => null,
            'submitted_by' => null,
            'registrar_approved_at' => null,
            'registrar_approved_by' => null,
            'ceo_approved_at' => null,
            'ceo_approved_by' => null,
            'published_at' => null,
            'published_by' => null,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.curriculum_version.reopened',
            'curriculum_versions',
            $version->id,
            ['status' => $previousStatus],
            ['status' => 'draft'],
            'Intake returned to draft for unit mapping edits',
            'success',
            $user->id,
            $request
        );

        return $version->fresh();
    }

    /**
     * @return \Illuminate\Support\Collection<string, CurriculumVersionPeriod>
     */
    public function periodsKeyed(CurriculumVersion $version): Collection
    {
        return CurriculumVersionPeriod::query()
            ->where('curriculum_version_id', $version->id)
            ->orderBy('semester')
            ->get()
            ->keyBy(fn (CurriculumVersionPeriod $period) => $this->periodKey($period->semester, $period->block_id));
    }

    /**
     * @param  array<int, array<string, mixed>>  $periods
     */
    public function syncPeriodDates(
        User $user,
        Department $hub,
        CurriculumVersion $version,
        array $periods,
        ?Request $request = null
    ): void {
        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);
        abort_unless($version->status !== 'superseded', 422, 'Superseded intakes cannot be edited.');

        foreach ($periods as $row) {
            $semester = (int) ($row['semester'] ?? 0);
            $blockId = isset($row['block_id']) && $row['block_id'] !== '' ? (int) $row['block_id'] : null;
            $startDate = $row['start_date'] ?? null;
            $endDate = $row['end_date'] ?? null;
            $learningStart = $row['learning_start_date'] ?? null;
            $learningEnd = $row['learning_end_date'] ?? null;
            $examStart = $row['exam_start_date'] ?? null;
            $examEnd = $row['exam_end_date'] ?? null;

            if ($semester < 1) {
                continue;
            }

            if ($startDate && ! $learningStart) {
                $learningStart = $startDate;
            }

            if ($endDate && ! $examEnd) {
                $examEnd = $endDate;
            }

            $this->validatePeriodDateRange($startDate, $endDate, "periods.{$semester}.end_date", 'End date must be on or after the start date.');
            $this->validatePeriodDateRange($learningStart, $learningEnd, "periods.{$semester}.learning_end_date", 'Learning end date must be on or after the learning start date.');
            $this->validatePeriodDateRange($examStart, $examEnd, "periods.{$semester}.exam_end_date", 'Exam end date must be on or after the exam start date.');
            $this->validateNestedPeriodDates($startDate, $endDate, $learningStart, $learningEnd, "periods.{$semester}.learning_start_date", 'Learning dates must fall within the semester dates.');
            $this->validateNestedPeriodDates($startDate, $endDate, $examStart, $examEnd, "periods.{$semester}.exam_start_date", 'Exam dates must fall within the semester dates.');
            $this->validateExamStartsAfterLearning($learningEnd, $examStart, $semester);

            CurriculumVersionPeriod::query()->updateOrCreate(
                [
                    'curriculum_version_id' => $version->id,
                    'semester' => $semester,
                    'block_id' => $blockId,
                ],
                [
                    'start_date' => $startDate ?: null,
                    'end_date' => $endDate ?: null,
                    'learning_start_date' => $learningStart ?: null,
                    'learning_end_date' => $learningEnd ?: null,
                    'exam_start_date' => $examStart ?: null,
                    'exam_end_date' => $examEnd ?: null,
                ]
            );
        }

        $this->auditService->log(
            'academics.curriculum_version.periods_updated',
            'curriculum_versions',
            $version->id,
            null,
            ['period_count' => count($periods)],
            'Intake semester dates updated',
            'success',
            $user->id,
            $request
        );
    }

    public function periodKey(int $semester, ?int $blockId): string
    {
        return $semester.':'.($blockId ?? '');
    }

    /**
     * @return Collection<int, CurriculumVersionUnit>
     */
    public function mappedUnits(CurriculumVersion $version): Collection
    {
        return CurriculumVersionUnit::query()
            ->with(['unit', 'block'])
            ->where('curriculum_version_id', $version->id)
            ->orderBy('semester')
            ->orderBy('display_order')
            ->orderBy('priority')
            ->get();
    }

    public function submit(User $user, Department $hub, CurriculumVersion $version, ?Request $request = null): CurriculumVersion
    {
        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);
        abort_unless($version->status === 'draft', 422);

        $mappedCount = CurriculumVersionUnit::query()->where('curriculum_version_id', $version->id)->count();

        if ($mappedCount === 0) {
            throw ValidationException::withMessages([
                'intake' => 'Assign at least one unit to this intake before submitting. Use “Add unit” on each semester below.',
            ]);
        }

        $inactiveMapped = CurriculumVersionUnit::query()
            ->where('curriculum_version_id', $version->id)
            ->whereHas('unit', fn ($query) => $query->where('status', '!=', 'active'))
            ->count();

        if ($inactiveMapped > 0) {
            throw ValidationException::withMessages([
                'intake' => "All mapped units must be active before submitting. {$inactiveMapped} unit(s) are still draft or pending registry approval - approve them in the unit catalog first.",
            ]);
        }

        $version->update([
            'status' => 'pending_registry',
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.curriculum_version.submitted',
            'curriculum_versions',
            $version->id,
            ['status' => 'draft'],
            ['status' => 'pending_registry'],
            'Intake curriculum submitted for registry verification',
            'success',
            $user->id,
            $request
        );

        return $version->fresh();
    }

    public function approveRegistry(User $user, Department $hub, CurriculumVersion $version, ?Request $request = null): CurriculumVersion
    {
        abort_unless($this->access->canApproveRegistry($user), 403);
        abort_unless($version->status === 'pending_registry', 422);

        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);

        $program = $version->program;
        $needsCeo = $program->status === 'pending_ceo';

        $version->update([
            'status' => $needsCeo ? 'pending_ceo' : 'published',
            'registrar_approved_at' => now(),
            'registrar_approved_by' => $user->id,
            'published_at' => $needsCeo ? null : now(),
            'published_by' => $needsCeo ? null : $user->id,
            'updated_at' => now(),
        ]);

        if (! $needsCeo) {
            $this->mirrorToProgramTemplate($version);
            $this->supersedePrevious($version);
        }

        $this->auditService->log(
            'academics.curriculum_version.registrar_approved',
            'curriculum_versions',
            $version->id,
            null,
            ['status' => $version->status],
            'Intake curriculum approved by registrar',
            'success',
            $user->id,
            $request
        );

        return $version->fresh('items.unit');
    }

    public function approveCeo(User $user, Department $hub, CurriculumVersion $version, ?Request $request = null): CurriculumVersion
    {
        abort_unless($this->access->canApproveCeo($user), 403);
        abort_unless($version->status === 'pending_ceo', 422);

        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);

        $version->update([
            'status' => 'published',
            'ceo_approved_at' => now(),
            'ceo_approved_by' => $user->id,
            'published_at' => now(),
            'published_by' => $user->id,
            'updated_at' => now(),
        ]);

        if ($version->program->status === 'pending_ceo') {
            $version->program->update([
                'status' => 'active',
                'approved_by_ceo_at' => now(),
                'approved_by_ceo_id' => $user->id,
            ]);
        }

        $this->mirrorToProgramTemplate($version);
        $this->supersedePrevious($version);

        $this->auditService->log(
            'academics.curriculum_version.ceo_approved',
            'curriculum_versions',
            $version->id,
            null,
            ['status' => 'published'],
            'Intake curriculum published after CEO approval',
            'success',
            $user->id,
            $request
        );

        return $version->fresh('items.unit');
    }

    private function copyUnits(CurriculumVersion $source, CurriculumVersion $target): void
    {
        $source->loadMissing('items');

        foreach ($source->items as $item) {
            CurriculumVersionUnit::create([
                'curriculum_version_id' => $target->id,
                'unit_id' => $item->unit_id,
                'semester' => $item->semester,
                'block_id' => $item->block_id,
                'is_compulsory' => $item->is_compulsory,
                'display_order' => $item->display_order,
                'priority' => $item->priority,
                'credit_hours' => $item->credit_hours,
                'contact_hours' => $item->contact_hours,
                'total_learning_hours' => $item->total_learning_hours,
            ]);
        }
    }

    private function copyPeriods(CurriculumVersion $source, CurriculumVersion $target): void
    {
        $source->loadMissing('periods');

        foreach ($source->periods as $period) {
            CurriculumVersionPeriod::create([
                'curriculum_version_id' => $target->id,
                'semester' => $period->semester,
                'block_id' => $period->block_id,
                'start_date' => $period->start_date,
                'end_date' => $period->end_date,
            ]);
        }
    }

    private function copyFromProgramTemplate(AcademicProgram $program, CurriculumVersion $target): void
    {
        $mappings = ProgramUnit::query()
            ->with('unit')
            ->where('program_id', $program->id)
            ->get();

        foreach ($mappings as $mapping) {
            CurriculumVersionUnit::create([
                'curriculum_version_id' => $target->id,
                'unit_id' => $mapping->unit_id,
                'semester' => $mapping->semester,
                'block_id' => $mapping->block_id,
                'is_compulsory' => $mapping->is_compulsory,
                'display_order' => $mapping->display_order,
                'priority' => $mapping->priority,
                'credit_hours' => $mapping->unit?->credit_hours ?? 0,
                'contact_hours' => $mapping->contact_hours ?: ($mapping->unit?->contact_hours ?? 0),
                'total_learning_hours' => $mapping->total_learning_hours ?: ($mapping->unit?->total_learning_hours ?? 0),
            ]);
        }
    }

    /** Keep program_units as a reusable template from the latest published intake. */
    private function mirrorToProgramTemplate(CurriculumVersion $version): void
    {
        $version->loadMissing(['items.unit', 'program']);
        $program = $version->program;

        ProgramUnit::query()->where('program_id', $program->id)->delete();

        foreach ($version->items as $item) {
            ProgramUnit::create([
                'program_id' => $program->id,
                'unit_id' => $item->unit_id,
                'semester' => $item->semester,
                'block_id' => $item->block_id,
                'is_compulsory' => $item->is_compulsory,
                'display_order' => $item->display_order,
                'priority' => $item->priority,
                'contact_hours' => $item->contact_hours,
                'total_learning_hours' => $item->total_learning_hours,
                'is_active' => 1,
            ]);
        }
    }

    private function supersedePrevious(CurriculumVersion $version): void
    {
        CurriculumVersion::query()
            ->where('program_id', $version->program_id)
            ->where('id', '!=', $version->id)
            ->where('status', 'published')
            ->update(['status' => 'superseded', 'updated_at' => now()]);
    }

    public function publishedVersionForProgram(int $programId): ?CurriculumVersion
    {
        return CurriculumVersion::query()
            ->with('items.unit')
            ->where('program_id', $programId)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->first();
    }

    /**
     * @return Collection<int, CurriculumVersion>
     */
    public function intakesForProgram(int $programId): Collection
    {
        return CurriculumVersion::query()
            ->where('program_id', $programId)
            ->orderByDesc('intake_year')
            ->orderByDesc('intake_month')
            ->orderByDesc('version_number')
            ->get();
    }

    public function resolveSelectedIntake(AcademicProgram $program, ?int $intakeId): ?CurriculumVersion
    {
        $intakes = $this->intakesForProgram($program->id);

        if ($intakeId) {
            return $intakes->firstWhere('id', $intakeId);
        }

        return $intakes->firstWhere('status', 'draft') ?? $intakes->first();
    }

    private function validatePeriodDateRange(?string $start, ?string $end, string $field, string $message): void
    {
        if ($start && $end && $start > $end) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function validateNestedPeriodDates(
        ?string $outerStart,
        ?string $outerEnd,
        ?string $innerStart,
        ?string $innerEnd,
        string $field,
        string $message
    ): void {
        if (! $outerStart || ! $outerEnd) {
            return;
        }

        foreach ([$innerStart, $innerEnd] as $date) {
            if ($date && ($date < $outerStart || $date > $outerEnd)) {
                throw ValidationException::withMessages([$field => $message]);
            }
        }
    }

    private function validateExamStartsAfterLearning(?string $learningEnd, ?string $examStart, int $semester): void
    {
        if ($learningEnd && $examStart && $examStart < $learningEnd) {
            throw ValidationException::withMessages([
                "periods.{$semester}.exam_start_date" => 'Exam start must be on or after the learning end date.',
            ]);
        }
    }
}
