<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcademicsAccessService
{
    /** Roles in the academics module that unlock the full academics hub (beyond Dean suggestion-box access). */
    public const FULL_ACADEMICS_ROLES = [
        'Super Admin',
        'CEO',
        'Head of Academics',
        'Academic Registrar',
        'HOD',
        'Lecturer/Tutor',
    ];

    public function __construct(
        protected RBACService $rbacService,
        protected DepartmentDashboardService $departmentDashboard,
    ) {}

    public function canAccessAll(User $user): bool
    {
        return $this->rbacService->hasAnyRole($user, ['Super Admin', 'CEO', 'Academic Registrar', 'Head of Academics']);
    }

    /**
     * Dean of Students with no other academics role may only use Student Voice → Suggestion box.
     */
    public function isSuggestionsOnly(User $user): bool
    {
        if (! $this->rbacService->hasRole($user, 'Dean of Students')) {
            return false;
        }

        if ($this->rbacService->isPlatformAdministrator($user)) {
            return false;
        }

        return ! $this->rbacService->hasAnyRole($user, self::FULL_ACADEMICS_ROLES);
    }

    public function canAccessFullAcademics(User $user): bool
    {
        return ! $this->isSuggestionsOnly($user);
    }

    public function canApproveRegistry(User $user): bool
    {
        return $this->canAccessAll($user)
            || $this->rbacService->hasPermission($user, 'academics.approve');
    }

    public function canApproveCeo(User $user): bool
    {
        return $this->rbacService->hasAnyRole($user, ['Super Admin', 'CEO']);
    }

    /**
     * @return list<int>
     */
    public function scopeDepartmentIds(Department $hub): array
    {
        $ids = $hub->academicsScopeDepartmentIds();

        if ($ids !== []) {
            return $ids;
        }

        return $hub->isAcademicsHub() ? [] : [(int) $hub->id];
    }

    /**
     * @return Collection<int, Department>
     */
    public function learningDepartmentsInScope(User $user, Department $hub): Collection
    {
        $scopeIds = $this->scopeDepartmentIds($hub);

        if ($scopeIds === []) {
            return collect();
        }

        return Department::query()
            ->validLearningDepartments()
            ->whereIn('id', $scopeIds)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('dept_name')
            ->get()
            ->filter(fn (Department $department) => $this->departmentDashboard->userCanAccessDepartment($user, $department)
                || ($hub->isAcademicsHub() && $this->canAccessAll($user)))
            ->values();
    }

    /**
     * @return Builder<AcademicProgram>
     */
    public function programsQueryForHub(User $user, Department $hub): Builder
    {
        $scopeIds = $this->scopeDepartmentIds($hub);

        $query = AcademicProgram::query()
            ->with('department:id,dept_name,dept_code')
            ->orderBy('program_name');

        if ($scopeIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->canAccessAll($user)) {
            return $query->whereIn('department_id', $scopeIds);
        }

        $allowed = $this->learningDepartmentsInScope($user, $hub)->pluck('id')->all();

        return $query->whereIn('department_id', $allowed);
    }

    public function userCanAccessProgramInHub(User $user, Department $hub, AcademicProgram $program): bool
    {
        $scopeIds = $this->scopeDepartmentIds($hub);

        if (! in_array((int) $program->department_id, $scopeIds, true)) {
            return false;
        }

        if ($this->canAccessAll($user)) {
            return true;
        }

        return $this->departmentDashboard->userCanAccessDepartment($user, $hub)
            || ($program->department && $this->departmentDashboard->userCanAccessDepartment($user, $program->department));
    }

    public function findProgramForHub(User $user, Department $hub, int $programId): AcademicProgram
    {
        $program = AcademicProgram::query()->with('department')->findOrFail($programId);
        abort_unless($this->userCanAccessProgramInHub($user, $hub, $program), 403);

        return $program;
    }

    /**
     * @return Collection<int, Unit>
     */
    public function unitsInScope(Department $hub, ?int $learningDepartmentId = null): Collection
    {
        $scopeIds = $learningDepartmentId
            ? [(int) $learningDepartmentId]
            : $this->scopeDepartmentIds($hub);

        if ($scopeIds === []) {
            return collect();
        }

        return Unit::query()
            ->with(['department:id,dept_name,dept_code', 'program:id,program_code,program_name,department_id'])
            ->where(function ($builder) use ($scopeIds) {
                $builder->whereIn('department_id', $scopeIds)
                    ->orWhereHas('program', fn ($programQuery) => $programQuery->whereIn('department_id', $scopeIds));
            })
            ->orderBy('display_priority')
            ->orderBy('unit_code')
            ->get();
    }
}
