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
        if ($department->isAcademicsHub()) {
            $hubModules = $this->academicsHubModules($user, $department);

            if ($hubModules !== []) {
                return $hubModules;
            }
        }

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
                'params' => array_merge(
                    ['department' => $department->id],
                    $module['params'] ?? []
                ),
                'coming_soon' => $module['coming_soon'] ?? false,
            ];
        }

        return $modules;
    }

    /**
     * @return 'hub'|'academic'|'operational'|'empty'
     */
    public function dashboardViewType(User $user, Department $department): string
    {
        if ($this->accessibleChildDepartments($user, $department)->isNotEmpty()) {
            return 'hub';
        }

        if ($this->modulesForDepartment($user, $department) !== []) {
            return $department->dept_category === 'academic' ? 'academic' : 'operational';
        }

        return 'empty';
    }

    public function resolveSection(\Illuminate\Http\Request $request, User $user, Department $department): string
    {
        $section = $request->string('section')->toString() ?: 'overview';

        if ($section === 'departments') {
            if ($this->accessibleChildDepartments($user, $department)->isEmpty()) {
                return 'overview';
            }

            return 'departments';
        }

        return 'overview';
    }

    /**
     * @return list<array{type: 'link'|'heading', label: string, route?: string, params?: array<string, mixed>, section?: string, coming_soon?: bool}>
     */
    public function sidebarNavigation(User $user, Department $department): array
    {
        $items = [
            [
                'type' => 'link',
                'label' => 'Overview',
                'route' => 'departments.show',
                'params' => ['department' => $department->id],
                'section' => 'overview',
            ],
        ];

        $children = $this->accessibleChildDepartments($user, $department);

        if ($children->isNotEmpty()) {
            $items[] = [
                'type' => 'link',
                'label' => 'Departments',
                'route' => 'departments.show',
                'params' => ['department' => $department->id, 'section' => 'departments'],
                'section' => 'departments',
            ];
        }

        $modules = $this->modulesForDepartment($user, $department);

        if ($modules !== []) {
            $items[] = ['type' => 'heading', 'label' => 'Tools'];

            foreach ($modules as $module) {
                $items[] = [
                    'type' => 'link',
                    'label' => $module['label'],
                    'route' => $module['route'],
                    'params' => $module['params'] ?? [],
                    'coming_soon' => $module['coming_soon'] ?? false,
                ];
            }
        }

        if ($department->parent_dept_id) {
            $parent = Department::query()->find($department->parent_dept_id);

            if ($parent && $this->userCanAccessDepartment($user, $parent)) {
                $items[] = ['type' => 'heading', 'label' => 'Navigation'];
                $items[] = [
                    'type' => 'link',
                    'label' => $parent->dept_name,
                    'route' => 'departments.show',
                    'params' => ['department' => $parent->id],
                ];
            }
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
     * @return array<string, int|string|null>
     */
    public function overviewStats(User $user, Department $department): array
    {
        $children = $this->accessibleChildDepartments($user, $department);
        $modules = $this->modulesForDepartment($user, $department);

        return [
            'child_count' => $children->count(),
            'tool_count' => count($modules),
            'category' => $this->categoryLabel($department),
            'view_type' => $this->dashboardViewType($user, $department),
        ];
    }

    public function cardDescription(Department $department): string
    {
        if ($department->isAcademicsHub()) {
            $count = (int) ($department->children_count ?? $department->children()->active()->count());

            return "Academics hub with {$count} learning ".($count === 1 ? 'department' : 'departments').'. Open curriculum tools, unit catalog, and programme builder.';
        }

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
                'route' => 'departments.academics.dashboard',
                'params' => [],
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
     * @return list<array{label: string, description: string, route: string, params: array<string, mixed>, coming_soon?: bool}>
     */
    private function academicsHubModules(User $user, Department $department): array
    {
        $modules = [];
        $params = ['department' => $department->id];

        if ($this->rbacService->hasPermission($user, 'academics.read')) {
            $modules[] = [
                'label' => 'Curriculum overview',
                'description' => 'Summary stats and quick links for programmes, units, and versions.',
                'route' => 'departments.academics.dashboard',
                'params' => $params,
            ];
            $modules[] = [
                'label' => 'Learning department profiles',
                'description' => 'Set curriculum profiles for CHS, ICT, Business, and other schools.',
                'route' => 'departments.academics.departments.index',
                'params' => $params,
            ];
            $modules[] = [
                'label' => 'Unit catalog',
                'description' => 'Create and approve units before mapping them to programmes.',
                'route' => 'departments.academics.units.index',
                'params' => $params,
            ];
            $modules[] = [
                'label' => 'Programme curriculum',
                'description' => 'Course length, terms per year, semester/block unit mapping, and versioning.',
                'route' => 'departments.academics.programs.index',
                'params' => $params,
            ];
        }

        if ($this->rbacService->hasPermission($user, 'academics.calendar')) {
            $modules[] = [
                'label' => 'Academic calendar',
                'description' => 'Configure academic years and intake terms.',
                'route' => 'departments.academics.calendar.index',
                'params' => $params,
            ];
        }

        return $modules;
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
