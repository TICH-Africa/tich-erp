<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Applicant;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;

class DepartmentDashboardService
{
    /**
     * Preferred landing routes when opening a department from the main dashboard.
     *
     * @var list<string>
     */
    private const ENTRY_ROUTE_PRIORITY = [
        'hr.dashboard',
        'finance.dashboard',
        'administration.dashboard',
        'qa.dashboard',
        'procurement.dashboard',
        'research.dashboard',
        'admissions.dashboard',
        'sis.dashboard',
        'sis.students.index',
        'departments.academics.dashboard',
        'admissions.applications.index',
        'departments.academics.programs.index',
    ];

    public function __construct(
        protected RBACService $rbacService,
        protected DepartmentModuleService $departmentModuleService,
    ) {}

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
     * @return list<array{label: string, description: string, route: string, params: array<string, mixed>, coming_soon?: bool, group?: string}>
     */
    public function modulesForDepartment(User $user, Department $department): array
    {
        if ($department->children()->active()->exists() && ! $department->isAcademicsHub() && $department->dept_code !== 'FIN') {
            return [];
        }

        $tools = $this->departmentModuleService->dashboardToolsForDepartment($department);
        $modules = [];

        foreach ($tools as $tool) {
            $permission = $tool['permission'] ?? null;

            if ($permission && ! $this->rbacService->hasPermission($user, $permission)) {
                continue;
            }

            $modules[] = [
                'label' => $tool['label'],
                'description' => $tool['description'],
                'route' => $tool['route'],
                'params' => $this->resolveToolParams($department, $tool),
                'coming_soon' => $tool['coming_soon'] ?? false,
                'group' => $tool['group'] ?? null,
            ];
        }

        return $modules;
    }

    /**
     * @return 'hub'|'learning'|'academic'|'operational'|'empty'
     */
    public function dashboardViewType(User $user, Department $department): string
    {
        if ($this->accessibleChildDepartments($user, $department)->isNotEmpty() && $department->dept_code !== 'FIN') {
            return 'hub';
        }

        if ($department->isLearningDepartment()) {
            return 'learning';
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
        if ($department->isLearningDepartment()) {
            return $this->learningDepartmentSidebarNavigation($user, $department);
        }

        $items = [
            [
                'type' => 'link',
                'label' => 'Overview',
                'route' => 'departments.show',
                'params' => ['department' => $department->getRouteKey()],
                'target_id' => $department->id,
                'section' => 'overview',
            ],
        ];

        $children = $this->accessibleChildDepartments($user, $department);

        if ($children->isNotEmpty()) {
            $items[] = [
                'type' => 'link',
                'label' => 'Departments',
                'route' => 'departments.show',
                'params' => ['department' => $department->getRouteKey(), 'section' => 'departments'],
                'target_id' => $department->id,
                'section' => 'departments',
            ];
        }

        $modules = $this->modulesForDepartment($user, $department);

        if ($modules !== [] && ($children->isEmpty() || $department->dept_code === 'FIN')) {
            $groupLabels = [
                'education' => 'Education',
                'admissions' => 'Admissions',
                'finance' => 'Finance',
                'hr' => 'Human resources',
                'finance' => 'Finance',
                'tools' => 'Tools',
            ];

            $grouped = collect($modules)->groupBy(fn (array $module) => $module['group'] ?? 'tools');

            foreach ($groupLabels as $groupKey => $groupLabel) {
                if (! $grouped->has($groupKey)) {
                    continue;
                }

                $items[] = ['type' => 'heading', 'label' => $groupLabel];

                foreach ($grouped->get($groupKey) as $module) {
                    $items[] = [
                        'type' => 'link',
                        'label' => $module['label'],
                        'route' => $module['route'],
                        'params' => $module['params'] ?? [],
                        'coming_soon' => $module['coming_soon'] ?? false,
                    ];
                }
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
                    'params' => ['department' => $parent->getRouteKey()],
                    'target_id' => $parent->id,
                    'section' => 'overview',
                ];

                if ($parent->isAcademicsHub()) {
                    $items[] = [
                        'type' => 'link',
                        'label' => 'All departments',
                        'route' => 'departments.show',
                        'params' => ['department' => $parent->getRouteKey(), 'section' => 'departments'],
                        'target_id' => $parent->id,
                        'section' => 'departments',
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @return list<array{type: 'link'|'heading', label: string, route?: string, params?: array<string, mixed>, section?: string, coming_soon?: bool, target_id?: int}>
     */
    private function learningDepartmentSidebarNavigation(User $user, Department $department): array
    {
        $items = [
            [
                'type' => 'link',
                'label' => 'Overview',
                'route' => 'departments.show',
                'params' => ['department' => $department->getRouteKey()],
                'target_id' => $department->id,
                'section' => 'overview',
            ],
        ];

        $modules = $this->modulesForDepartment($user, $department);

        if ($modules === []) {
            return $items;
        }

        $groupLabels = [
            'education' => 'Education',
            'admissions' => 'Admissions',
            'finance' => 'Finance',
            'tools' => 'Tools',
        ];

        $grouped = collect($modules)->groupBy(fn (array $module) => $module['group'] ?? 'tools');

        foreach ($groupLabels as $groupKey => $groupLabel) {
            if (! $grouped->has($groupKey)) {
                continue;
            }

            $items[] = ['type' => 'heading', 'label' => $groupLabel];

            foreach ($grouped->get($groupKey) as $module) {
                $items[] = [
                    'type' => 'link',
                    'label' => $module['label'],
                    'route' => $module['route'],
                    'params' => $module['params'] ?? [],
                    'coming_soon' => $module['coming_soon'] ?? false,
                ];
            }
        }

        return $items;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function overviewStats(User $user, Department $department): array
    {
        $children = $this->accessibleChildDepartments($user, $department);
        $modules = $this->modulesForDepartment($user, $department);

        $stats = [
            'child_count' => $children->count(),
            'tool_count' => count($modules),
            'category' => $this->categoryLabel($department),
            'view_type' => $this->dashboardViewType($user, $department),
        ];

        if ($department->isLearningDepartment()) {
            $stats['program_count'] = AcademicProgram::query()
                ->where('department_id', $department->id)
                ->count();
            $stats['unit_count'] = Unit::query()
                ->where('department_id', $department->id)
                ->count();
            $stats['pending_applications'] = $this->pendingApplicationsCount($department);
            $stats['curriculum_profile'] = $department->curriculum_profile ?? 'standard';
        }

        return $stats;
    }

    public function pendingApplicationsCount(Department $department): int
    {
        if (! $department->isLearningDepartment()) {
            return 0;
        }

        return Applicant::query()
            ->where(function ($query) use ($department) {
                $query->where('handling_department_id', $department->id)
                    ->orWhereHas('program', fn ($programQuery) => $programQuery->where('department_id', $department->id));
            })
            ->whereIn('status', ['submitted', 'academic_review'])
            ->whereIn('academic_review_status', ['pending', 'under_review', 'shortlisted'])
            ->count();
    }

    /**
     * @param  list<int>  $programIds
     * @return array<int, int>
     */
    public function pendingApplicationsCountByProgram(array $programIds): array
    {
        if ($programIds === []) {
            return [];
        }

        return Applicant::query()
            ->whereIn('program_id', $programIds)
            ->whereIn('status', ['submitted', 'academic_review'])
            ->whereIn('academic_review_status', ['pending', 'under_review', 'shortlisted'])
            ->groupBy('program_id')
            ->selectRaw('program_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'program_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @return Collection<int, AcademicProgram>
     */
    public function programsForDepartment(Department $department): Collection
    {
        if (! $department->isLearningDepartment()) {
            return collect();
        }

        return AcademicProgram::query()
            ->where('department_id', $department->id)
            ->orderBy('program_name')
            ->get();
    }

    public function entryUrlForDepartment(User $user, Department $department): string
    {
        $moduleHome = app(DepartmentBudgetingService::class)->moduleHomeUrlForDepartment($department);
        if ($moduleHome) {
            return $moduleHome;
        }

        if ($this->accessibleChildDepartments($user, $department)->isNotEmpty()
            && $this->modulesForDepartment($user, $department) === []) {
            return route('dashboard');
        }

        $modules = collect($this->modulesForDepartment($user, $department))
            ->filter(fn (array $module) => empty($module['coming_soon']))
            ->sortBy(fn (array $module) => array_search($module['route'], self::ENTRY_ROUTE_PRIORITY, true) ?: 99)
            ->values();

        foreach ($modules as $module) {
            return route($module['route'], $module['params'] ?? []);
        }

        return route('dashboard');
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
            'academic' => 'Programmes, unit catalog, applications, and curriculum for this school.',
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
     * @param  array<string, mixed>  $tool
     * @return array<string, mixed>
     */
    private function resolveToolParams(Department $department, array $tool): array
    {
        $params = $tool['params'] ?? [];
        $route = (string) ($tool['route'] ?? '');

        if (str_starts_with($route, 'departments.academics.')) {
            if ($department->isLearningDepartment()) {
                $params['learning_department'] = $department->id;
            }

            return $params;
        }

        if ($department->isLearningDepartment()) {
            if ($route === 'admissions.applications.index') {
                $params['department'] = $department->id;
            } else {
                $params['learning_department'] = $department->id;
            }

            return $params;
        }

        $params['department'] = $department->id;

        return $params;
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
