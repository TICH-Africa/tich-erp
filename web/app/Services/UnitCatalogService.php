<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersionUnit;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UnitCatalogService
{
    public function __construct(
        protected AuditService $auditService,
        protected AcademicsAccessService $access,
    ) {}

    /**
     * @return Collection<int, Unit>
     */
    public function listForHub(
        User $user,
        Department $hub,
        ?int $learningDepartmentId = null,
        ?string $status = null
    ): Collection {
        $authorizedId = $this->access->authorizedLearningDepartmentId($user, $hub, $learningDepartmentId);
        $scopeIds = $authorizedId !== null ? [$authorizedId] : $this->access->accessibleLearningDepartmentIds($user, $hub);

        if ($scopeIds === []) {
            return collect();
        }

        $query = Unit::query()
            ->with(['department:id,dept_name,dept_code', 'program:id,program_code,program_name,department_id'])
            ->where(function ($builder) use ($scopeIds) {
                $builder->whereIn('department_id', $scopeIds)
                    ->orWhereHas('program', fn ($programQuery) => $programQuery->whereIn('department_id', $scopeIds));
            })
            ->orderBy('display_priority')
            ->orderBy('unit_code');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function create(User $user, Department $hub, array $data, ?Request $request = null): Unit
    {
        $scopeIds = $this->access->accessibleLearningDepartmentIds($user, $hub);
        abort_unless(
            in_array((int) $data['department_id'], $scopeIds, true),
            422,
            'That department is outside your academics access.'
        );

        $payload = [
            'unit_code' => $data['unit_code'],
            'unit_name' => $data['unit_name'],
            'description' => $data['description'] ?? null,
            'department_id' => (int) $data['department_id'],
            'semester' => $data['semester'] ?? 1,
            'block' => $data['block'] ?? null,
            'credit_hours' => $data['credit_hours'] ?? 0,
            'contact_hours' => $data['contact_hours'] ?? 0,
            'total_learning_hours' => $data['total_learning_hours'] ?? ($data['contact_hours'] ?? 0),
            'display_priority' => $data['display_priority'] ?? 0,
            'is_core' => $data['is_core'] ?? true,
            'is_practical' => $data['is_practical'] ?? false,
            'prerequisite_unit_id' => $data['prerequisite_unit_id'] ?? null,
            'co_requisite_unit_id' => $data['co_requisite_unit_id'] ?? null,
            'status' => 'draft',
            'created_by' => $user->id,
            'created_at' => now(),
        ];

        if (! empty($data['program_id'])) {
            $program = AcademicProgram::query()->find((int) $data['program_id']);
            abort_unless(
                $program && in_array((int) $program->department_id, $scopeIds, true),
                422,
                'That programme belongs to another department.'
            );

            $payload['program_id'] = (int) $data['program_id'];
        }

        $unit = Unit::create($payload);

        $this->auditService->log(
            'academics.unit.created',
            'units',
            $unit->id,
            null,
            $unit->only(['unit_code', 'unit_name', 'department_id', 'status']),
            'Unit catalog entry created',
            'success',
            $user->id,
            $request
        );

        return $unit;
    }

    /**
     * The fields an officer can change on the catalog form, labelled the way the screen names them
     * so a saved edit can be reported back to them.
     *
     * @var array<string, string>
     */
    public const EDITABLE_FIELDS = [
        'unit_code' => 'code',
        'unit_name' => 'name',
        'description' => 'description',
        'semester' => 'year and semester',
        'credit_hours' => 'credit hours',
        'contact_hours' => 'contact hours',
        'total_learning_hours' => 'total learning hours',
        'is_core' => 'core flag',
        'is_practical' => 'practical flag',
    ];

    /**
     * @return array{unit: Unit, changes: array<int, string>, intake_moved: bool}
     */
    public function update(User $user, Department $hub, Unit $unit, array $data, ?Request $request = null): array
    {
        $this->assertUnitInScope($user, $hub, $unit);
        abort_unless($unit->isEditable(), 422, 'This unit can no longer be edited.');

        $payload = [
            'unit_code' => $data['unit_code'],
            'unit_name' => $data['unit_name'],
            'description' => $data['description'] ?? null,
            'semester' => $data['semester'] ?? $unit->semester,
            'block' => $data['block'] ?? $unit->block,
            'credit_hours' => $data['credit_hours'] ?? $unit->credit_hours,
            'contact_hours' => $data['contact_hours'] ?? $unit->contact_hours,
            'total_learning_hours' => $data['total_learning_hours'] ?? $unit->total_learning_hours,
            'display_priority' => $data['display_priority'] ?? $unit->display_priority,
            'is_core' => $data['is_core'] ?? $unit->is_core,
            'is_practical' => $data['is_practical'] ?? $unit->is_practical,
            'prerequisite_unit_id' => $data['prerequisite_unit_id'] ?? $unit->prerequisite_unit_id,
            'co_requisite_unit_id' => $data['co_requisite_unit_id'] ?? $unit->co_requisite_unit_id,
            'updated_at' => now(),
        ];

        $changes = [];

        foreach (self::EDITABLE_FIELDS as $field => $label) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $before = $unit->{$field};
            $after = $payload[$field];

            $same = match (true) {
                in_array($field, ['is_core', 'is_practical'], true) => (bool) $before === (bool) $after,
                is_numeric($before) && is_numeric($after) => (float) $before === (float) $after,
                default => (string) ($before ?? '') === (string) ($after ?? ''),
            };

            if (! $same) {
                $changes[] = $label;
            }
        }

        $old = $unit->only(['unit_code', 'unit_name', 'contact_hours', 'total_learning_hours', 'status']);

        $unit->update($payload);

        // Editing the period from an intake's catalog moves that intake's mapping with the unit.
        // Leaving the old row behind showed the unit on two years at once, and the timetable and
        // registration kept following the period the officer had just corrected.
        $intakeMoved = false;
        $intakeId = (int) ($data['edit_intake'] ?? 0);

        if ($intakeId > 0 && in_array('year and semester', $changes, true)) {
            $intakeMoved = CurriculumVersionUnit::query()
                ->where('curriculum_version_id', $intakeId)
                ->where('unit_id', $unit->id)
                ->update(['semester' => (int) $payload['semester']]) > 0;
        }

        $this->auditService->log(
            'academics.unit.updated',
            'units',
            $unit->id,
            $old,
            $unit->only(['unit_code', 'unit_name', 'contact_hours', 'total_learning_hours', 'status']),
            'Unit catalog entry updated'.($changes === [] ? '' : ': '.implode(', ', $changes)),
            'success',
            $user->id,
            $request
        );

        return ['unit' => $unit->fresh(), 'changes' => $changes, 'intake_moved' => $intakeMoved];
    }

    public function submitForRegistry(User $user, Department $hub, Unit $unit, ?Request $request = null): Unit
    {
        $this->assertUnitInScope($user, $hub, $unit);
        abort_unless(in_array($unit->status, ['draft', 'pending_registry'], true), 422);

        $unit->update([
            'status' => 'pending_registry',
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.unit.submitted',
            'units',
            $unit->id,
            null,
            ['status' => 'pending_registry'],
            'Unit submitted for registry verification',
            'success',
            $user->id,
            $request
        );

        return $unit->fresh();
    }

    public function approveRegistry(User $user, Department $hub, Unit $unit, ?Request $request = null): Unit
    {
        $this->assertUnitInScope($user, $hub, $unit);
        abort_unless($this->access->canApproveRegistry($user), 403, 'Only the Academic Registrar can verify a unit.');
        abort_unless($unit->status === 'pending_registry', 422);

        $unit->update([
            'status' => 'active',
            'registrar_approved_at' => now(),
            'registrar_approved_by' => $user->id,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.unit.approved',
            'units',
            $unit->id,
            ['status' => 'pending_registry'],
            ['status' => 'active'],
            'Unit approved by Academic Registrar',
            'success',
            $user->id,
            $request
        );

        return $unit->fresh();
    }

    private function assertUnitInScope(User $user, Department $hub, Unit $unit): void
    {
        $scopeIds = $this->access->accessibleLearningDepartmentIds($user, $hub);
        $inScope = in_array((int) $unit->department_id, $scopeIds, true)
            || ($unit->program && in_array((int) $unit->program->department_id, $scopeIds, true));

        abort_unless($inScope, 403, 'That unit belongs to another department.');
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            'draft' => 'Draft',
            'pending_registry' => 'Pending registry',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }
}
