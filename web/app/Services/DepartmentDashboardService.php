<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;

class DepartmentDashboardService
{
    public function __construct(protected RBACService $rbacService) {}

    public function mainDepartmentsForUser(User $user): Collection
    {
        $query = Department::query()
            ->with(['group', 'campus'])
            ->withCount(['children' => fn ($childQuery) => $childQuery->where('is_active', true)])
            ->main()
            ->active()
            ->orderBy('display_order')
            ->orderBy('dept_name');

        if ($this->rbacService->hasRole($user, 'Super Admin')) {
            return $query->get();
        }

        $userDepartmentIds = $this->rbacService->getUserDepartmentIds($user);

        if ($userDepartmentIds === []) {
            return collect();
        }

        $parentMap = Department::parentMap();
        $mainIds = collect($userDepartmentIds)
            ->map(fn (int $departmentId) => Department::resolveRootIdFromMap($departmentId, $parentMap))
            ->unique()
            ->values()
            ->all();

        return $query->whereIn('id', $mainIds)->get();
    }

    public function userCanAccessDepartment(User $user, Department $department): bool
    {
        if ($this->rbacService->hasRole($user, 'Super Admin')) {
            return true;
        }

        $userDepartmentIds = $this->rbacService->getUserDepartmentIds($user);

        if ($userDepartmentIds === []) {
            return false;
        }

        $scopeIds = $department->selfAndDescendantIds();
        $parentMap = Department::parentMap();

        foreach ($userDepartmentIds as $userDepartmentId) {
            if (in_array($userDepartmentId, $scopeIds, true)) {
                return true;
            }

            if (Department::resolveRootIdFromMap($userDepartmentId, $parentMap) === (int) $department->id) {
                return true;
            }

            if ($this->departmentIsAncestorOf((int) $userDepartmentId, (int) $department->id, $parentMap)) {
                return true;
            }
        }

        return false;
    }

    public function accessibleChildDepartments(User $user, Department $department): Collection
    {
        $children = $department->children()->active()->orderBy('display_order')->orderBy('dept_name')->get();

        if ($this->rbacService->hasRole($user, 'Super Admin')) {
            return $children;
        }

        return $children->filter(fn (Department $child) => $this->userCanAccessDepartment($user, $child))->values();
    }

    /**
     * @return list<array{label: string, description: string, route: string, params: array<string, mixed>, coming_soon?: bool}>
     */
    public function modulesForDepartment(User $user, Department $department): array
    {
        if ($department->children()->active()->exists()) {
            return [];
        }

        $modules = [];

        if ($this->shouldOfferAdmissions($department) && $this->rbacService->hasPermission($user, 'admissions.read')) {
            if ($department->dept_code === 'ADM') {
                $modules[] = [
                    'label' => 'Approval dashboard',
                    'description' => 'Verify, accept, and reject student onboarding applications.',
                    'route' => 'admissions.dashboard',
                    'params' => [],
                ];
            } else {
                $modules[] = [
                    'label' => 'Application approvals',
                    'description' => 'Review onboarding applications for this academic department.',
                    'route' => 'admissions.applications.index',
                    'params' => ['department' => $department->id, 'status' => 'pending'],
                ];
            }
        }

        foreach ($this->departmentModuleMap() as $deptCode => $module) {
            if ($department->dept_code !== $deptCode) {
                continue;
            }

            if (! $this->rbacService->hasPermission($user, $module['permission'])) {
                continue;
            }

            $modules[] = [
                'label' => $module['label'],
                'description' => $module['description'],
                'route' => $module['route'],
                'params' => $module['params'] ?? [],
                'coming_soon' => $module['coming_soon'] ?? false,
            ];
        }

        return $modules;
    }

    public function cardDescription(Department $department): string
    {
        if (($department->children_count ?? 0) > 0) {
            $count = (int) $department->children_count;

            return "Contains {$count} ".($count === 1 ? 'unit' : 'units').'. Open to browse departments and tools.';
        }

        return match ($department->dept_category) {
            'academic' => 'Academic programmes, applications, and student records.',
            'administrative' => 'Department operations, workflows, and records.',
            default => 'Department workspace and tools.',
        };
    }

    public function categoryLabel(Department $department): string
    {
        if ($department->dept_category === 'academic' && $department->parent_dept_id !== null) {
            return 'Academic department';
        }

        return match ($department->dept_category) {
            'academic' => 'Academic unit',
            'administrative' => 'Administrative unit',
            default => 'Department',
        };
    }

    /**
     * @return array<string, array{permission: string, label: string, description: string, route: string, params?: array<string, mixed>, coming_soon?: bool}>
     */
    private function departmentModuleMap(): array
    {
        return [
            'HR' => [
                'permission' => 'hr.read',
                'label' => 'Human resources',
                'description' => 'Staff contracts, leave, and recruitment.',
                'route' => 'dashboard',
                'coming_soon' => true,
            ],
            'FIN' => [
                'permission' => 'finance.read',
                'label' => 'Finance',
                'description' => 'Fees, invoices, payroll, and procurement.',
                'route' => 'dashboard',
                'coming_soon' => true,
            ],
            'PRC' => [
                'permission' => 'finance.read',
                'label' => 'Procurement & logistics',
                'description' => 'Purchasing, inventory, and supplier management.',
                'route' => 'dashboard',
                'coming_soon' => true,
            ],
            'ACAD' => [
                'permission' => 'academics.read',
                'label' => 'Curriculum hub',
                'description' => 'Course versioning, units, department mapping, and calendar.',
                'route' => 'academics.dashboard',
            ],
            'SIS' => [
                'permission' => 'students.read',
                'label' => 'Student Information System',
                'description' => '360° student biodata and enrolment records.',
                'route' => 'sis.students.index',
            ],
        ];
    }

    private function shouldOfferAdmissions(Department $department): bool
    {
        return $department->dept_code === 'ADM' || $department->dept_category === 'academic';
    }

    /**
     * @param  array<int, int|null>  $parentMap
     */
    private function departmentIsAncestorOf(int $ancestorId, int $departmentId, array $parentMap): bool
    {
        $current = $departmentId;

        while (! empty($parentMap[$current])) {
            $current = (int) $parentMap[$current];

            if ($current === $ancestorId) {
                return true;
            }
        }

        return false;
    }
}
