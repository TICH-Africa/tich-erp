<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AcademicsAccessService
{
    public function __construct(protected RBACService $rbacService) {}

    public function canAccessAll(User $user): bool
    {
        return $this->rbacService->hasAnyRole($user, ['Super Admin', 'CEO', 'Academic Registrar', 'Principal', 'Dean']);
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
     * @return Collection<int, Department>
     */
    public function learningDepartmentsForUser(User $user): Collection
    {
        $query = Department::query()
            ->where('dept_category', 'academic')
            ->whereNotNull('parent_dept_id')
            ->where('is_active', true)
            ->orderBy('dept_name');

        if ($this->canAccessAll($user)) {
            return $query->get();
        }

        $departmentIds = $this->rbacService->getUserDepartmentIds($user);

        if ($departmentIds === []) {
            return collect();
        }

        $parentMap = Department::parentMap();
        $allowedRoots = collect($departmentIds)
            ->map(fn (int $id) => Department::resolveRootIdFromMap($id, $parentMap))
            ->unique()
            ->values()
            ->all();

        return $query->get()->filter(function (Department $department) use ($departmentIds, $parentMap) {
            if (in_array($department->id, $departmentIds, true)) {
                return true;
            }

            $scopeIds = $department->selfAndDescendantIds();

            return count(array_intersect($departmentIds, $scopeIds)) > 0;
        })->values();
    }

    public function userCanAccessDepartment(User $user, Department $department): bool
    {
        if (! $department->isLearningDepartment()) {
            return $this->canAccessAll($user);
        }

        if ($this->canAccessAll($user)) {
            return true;
        }

        return $this->learningDepartmentsForUser($user)->contains('id', $department->id);
    }

    public function userCanAccessProgram(User $user, AcademicProgram $program): bool
    {
        $department = $program->department;

        return $department && $this->userCanAccessDepartment($user, $department);
    }

    /**
     * @return Builder<AcademicProgram>
     */
    public function programsQueryForUser(User $user): Builder
    {
        $query = AcademicProgram::query()->with('department:id,dept_name,dept_code');

        if ($this->canAccessAll($user)) {
            return $query->orderBy('program_name');
        }

        $departmentIds = $this->learningDepartmentsForUser($user)->pluck('id')->all();

        return $query->whereIn('department_id', $departmentIds)->orderBy('program_name');
    }

    public function findDepartmentForUser(User $user, int $departmentId): Department
    {
        $department = Department::query()->findOrFail($departmentId);
        abort_unless($this->userCanAccessDepartment($user, $department), 403);

        return $department;
    }

    public function findProgramForUser(User $user, int $programId): AcademicProgram
    {
        $program = AcademicProgram::query()->with('department')->findOrFail($programId);
        abort_unless($this->userCanAccessProgram($user, $program), 403);

        return $program;
    }
}
