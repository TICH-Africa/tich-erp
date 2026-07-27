<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UnitCatalogService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * @return Collection<int, Unit>
     */
    public function listForHub(Department $hub, ?int $learningDepartmentId = null, ?string $status = null): Collection
    {
        $scopeIds = $learningDepartmentId
            ? [(int) $learningDepartmentId]
            : $hub->academicsScopeDepartmentIds();

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
        $scopeIds = $hub->academicsScopeDepartmentIds();
        abort_unless(in_array((int) $data['department_id'], $scopeIds, true), 422, 'Invalid department for this academics hub.');

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

    public function update(User $user, Department $hub, Unit $unit, array $data, ?Request $request = null): Unit
    {
        $this->assertUnitInHub($hub, $unit);
        abort_unless($unit->isEditable(), 422, 'This unit can no longer be edited.');

        $old = $unit->only(['unit_code', 'unit_name', 'contact_hours', 'total_learning_hours', 'status']);

        $unit->update([
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
            'prerequisite_unit_id' => $data['prerequisite_unit_id'] ?? null,
            'co_requisite_unit_id' => $data['co_requisite_unit_id'] ?? null,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.unit.updated',
            'units',
            $unit->id,
            $old,
            $unit->only(['unit_code', 'unit_name', 'contact_hours', 'total_learning_hours', 'status']),
            'Unit catalog entry updated',
            'success',
            $user->id,
            $request
        );

        return $unit->fresh();
    }

    public function submitForRegistry(User $user, Department $hub, Unit $unit, ?Request $request = null): Unit
    {
        $this->assertUnitInHub($hub, $unit);
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
        $this->assertUnitInHub($hub, $unit);
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

    private function assertUnitInHub(Department $hub, Unit $unit): void
    {
        $scopeIds = $hub->academicsScopeDepartmentIds();
        $inScope = in_array((int) $unit->department_id, $scopeIds, true)
            || ($unit->program && in_array((int) $unit->program->department_id, $scopeIds, true));

        abort_unless($inScope, 403);
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
