<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentModuleService
{
    public const ACADEMICS_MODULE_KEY = 'academics';

    /**
     * @return list<array{key: string, label: string, description: string, permission: string, eligible_categories: list<string>, children: list<array<string, mixed>>}>
     */
    public function catalog(): array
    {
        return config('tich-department-modules.modules', []);
    }

    /**
     * @return list<string>
     */
    public function validModuleKeys(): array
    {
        return collect($this->catalog())->pluck('key')->all();
    }

    public function findModule(string $key): ?array
    {
        foreach ($this->catalog() as $module) {
            if ($module['key'] === $key) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @return list<string> Top-level module keys assigned to the department.
     */
    public function assignedModuleKeys(Department $department): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('department_modules')) {
            return [];
        }

        return DB::table('department_modules')
            ->where('department_id', $department->id)
            ->orderBy('module_key')
            ->pluck('module_key')
            ->all();
    }

    /**
     * @param  list<string>  $moduleKeys
     * @return list<string> All submodule keys inherited from assigned parents.
     */
    public function expandModuleKeys(array $moduleKeys): array
    {
        $expanded = [];

        foreach ($moduleKeys as $key) {
            $module = $this->findModule($key);

            if (! $module) {
                continue;
            }

            $expanded[] = $key;

            foreach ($module['children'] ?? [] as $child) {
                $expanded[] = $child['key'];
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @return list<string>
     */
    public function effectiveModuleKeys(Department $department): array
    {
        return $this->expandModuleKeys($this->assignedModuleKeys($department));
    }

    public function departmentHasModule(Department $department, string $moduleKey): bool
    {
        return in_array($moduleKey, $this->effectiveModuleKeys($department), true);
    }

    public function canHostLearningDepartments(Department $department): bool
    {
        return $department->isMainDepartment()
            && $this->departmentHasModule($department, self::ACADEMICS_MODULE_KEY);
    }

    /**
     * @return list<int>
     */
    public function departmentIdsHostingLearningDepartments(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('department_modules')) {
            return [];
        }

        $rows = DB::table('department_modules')
            ->join('departments', 'departments.id', '=', 'department_modules.department_id')
            ->where('department_modules.module_key', self::ACADEMICS_MODULE_KEY)
            ->whereNull('departments.parent_dept_id')
            ->where('departments.is_active', 1)
            ->pluck('departments.id');

        return $rows->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string>
     */
    public function learningDepartmentHierarchyErrors(array $validated, ?Department $department = null): array
    {
        $errors = [];
        $category = (string) ($validated['dept_category'] ?? '');
        $parentId = ! empty($validated['parent_dept_id']) ? (int) $validated['parent_dept_id'] : null;
        $requestedModules = is_array($validated['module_keys'] ?? null)
            ? array_values($validated['module_keys'])
            : ($department ? $this->assignedModuleKeys($department) : []);
        $hasAcademicsModule = in_array(self::ACADEMICS_MODULE_KEY, $requestedModules, true);

        if ($category === 'academic') {
            if ($parentId === null) {
                // Top-level academic hub is allowed when Academics module is assigned.
                if (! $hasAcademicsModule) {
                    $errors['module_keys'] = 'Assign the Academics module to make this a top-level academic hub, or place it under a parent that already has Academics.';
                    $errors['parent_dept_id'] = 'Academic learning departments must belong under a department with the Academics module.';
                }

                return $errors;
            }

            $parent = Department::query()->find($parentId);

            if (! $parent) {
                $errors['parent_dept_id'] = 'Select a valid parent department.';

                return $errors;
            }

            if (! $parent->isMainDepartment()) {
                $errors['parent_dept_id'] = 'Learning departments must sit directly under a top-level department with the Academics module.';
            } elseif (! $this->canHostLearningDepartments($parent)) {
                $errors['parent_dept_id'] = 'The parent department must have the Academics module enabled.';
            }
        } elseif ($parentId !== null) {
            $errors['parent_dept_id'] = 'Only academic learning departments can be placed under another department.';
        }

        if ($department !== null && $department->isMainDepartment()) {
            if (is_array($validated['module_keys'] ?? null) && ! $hasAcademicsModule) {
                $hasLearningChildren = Department::query()
                    ->where('parent_dept_id', $department->id)
                    ->where('dept_category', 'academic')
                    ->exists();

                if ($hasLearningChildren) {
                    $errors['module_keys'] = 'Cannot remove the Academics module while learning departments are assigned under this department.';
                }
            }
        }

        return $errors;
    }

    /**
     * Keep only module keys allowed for the department category.
     *
     * @param  list<string>  $moduleKeys
     * @return list<string>
     */
    public function filterKeysForCategory(string $category, array $moduleKeys): array
    {
        $allowed = $this->assignableModulesForCategory($category)->pluck('key')->all();

        return collect($moduleKeys)
            ->filter(fn (string $key) => in_array($key, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inheritedChildrenForModule(string $moduleKey): array
    {
        $module = $this->findModule($moduleKey);

        return $module['children'] ?? [];
    }

    /**
     * @param  list<string>  $moduleKeys
     */
    public function syncModules(Department $department, array $moduleKeys, ?int $assignedBy = null): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('department_modules')) {
            throw new \RuntimeException('department_modules table is missing. Run migrations / import production.sql.');
        }

        $validKeys = $this->validModuleKeys();
        $moduleKeys = collect($moduleKeys)
            ->filter(fn (string $key) => in_array($key, $validKeys, true))
            ->unique()
            ->values()
            ->all();

        DB::table('department_modules')
            ->where('department_id', $department->id)
            ->whereNotIn('module_key', $moduleKeys)
            ->delete();

        foreach ($moduleKeys as $key) {
            DB::table('department_modules')->updateOrInsert(
                [
                    'department_id' => $department->id,
                    'module_key' => $key,
                ],
                [
                    'assigned_at' => now(),
                    'assigned_by' => $assignedBy,
                ]
            );
        }
    }

    /**
     * @return list<string>
     */
    public function defaultModulesForCategory(string $category): array
    {
        return config("tich-department-modules.category_defaults.{$category}", []);
    }

    /**
     * @return list<string>
     */
    public function legacyModulesForDeptCode(string $deptCode): array
    {
        return config("tich-department-modules.legacy_dept_code_modules.{$deptCode}", []);
    }

    /**
     * @return array<int, list<string>> department_id => module keys
     */
    public function assignedModulesByDepartmentIds(array $departmentIds): array
    {
        if ($departmentIds === [] || ! \Illuminate\Support\Facades\Schema::hasTable('department_modules')) {
            return [];
        }

        $rows = DB::table('department_modules')
            ->whereIn('department_id', $departmentIds)
            ->orderBy('module_key')
            ->get(['department_id', 'module_key']);

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row->department_id][] = $row->module_key;
        }

        return $map;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function labelsForKeys(array $moduleKeys): array
    {
        $catalog = collect($this->catalog())->keyBy('key');

        return collect($moduleKeys)
            ->map(function (string $key) use ($catalog) {
                $module = $catalog->get($key);

                return [
                    'key' => $key,
                    'label' => $module['label'] ?? $key,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Resolve dashboard context for a department.
     *
     * @return 'hub'|'learning'|'operational'
     */
    public function departmentContext(Department $department): string
    {
        if ($department->isAcademicsHub()) {
            return 'hub';
        }

        if ($department->isLearningDepartment()) {
            return 'learning';
        }

        return 'operational';
    }

    /**
     * @return list<array{label: string, description: string, route: string, params: array<string, mixed>, group?: string, coming_soon?: bool}>
     */
    public function dashboardToolsForDepartment(Department $department): array
    {
        $context = $this->departmentContext($department);
        $assignedParents = $this->assignedModuleKeys($department);
        $tools = [];

        foreach ($assignedParents as $parentKey) {
            foreach ($this->inheritedChildrenForModule($parentKey) as $child) {
                $childContext = $child['context'] ?? 'any';

                if ($childContext !== 'any' && $childContext !== $context) {
                    continue;
                }

                $tools[$child['key']] = [
                    'label' => $child['label'],
                    'description' => $child['description'] ?? '',
                    'route' => $child['route'],
                    'params' => $child['params'] ?? [],
                    'group' => $child['group'] ?? null,
                    'coming_soon' => $child['coming_soon'] ?? false,
                    'permission' => $child['permission'] ?? null,
                ];
            }
        }

        return array_values($tools);
    }

    /**
     * Filter assignable modules for a department category.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function assignableModulesForCategory(string $category): Collection
    {
        return collect($this->catalog())->filter(function (array $module) use ($category) {
            $eligible = $module['eligible_categories'] ?? [];

            return $eligible === [] || in_array($category, $eligible, true);
        })->values();
    }

    /**
     * @return list<string>
     */
    public function dashboardPermissionsForDepartment(Department $department): array
    {
        $assigned = $this->assignedModuleKeys($department);
        $permissions = [];

        foreach (config('tich-department-modules.dashboard_permission_modules', []) as $permission => $moduleKey) {
            if ($moduleKey === null || in_array($moduleKey, $assigned, true)) {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }

    public function departmentSupportsDashboardPermission(Department $department, string $permissionKey): bool
    {
        $moduleKey = config("tich-department-modules.dashboard_permission_modules.{$permissionKey}");

        if ($moduleKey === null) {
            return true;
        }

        return $this->departmentHasModule($department, $moduleKey);
    }

    public function moduleKeyForPermission(string $permission): ?string
    {
        foreach ($this->catalog() as $module) {
            if (($module['permission'] ?? null) === $permission) {
                return $module['key'];
            }

            foreach ($module['children'] ?? [] as $child) {
                if (($child['permission'] ?? null) === $permission) {
                    return $module['key'];
                }
            }
        }

        foreach (config('tich-department-modules.dashboard_permission_modules', []) as $permissionKey => $moduleKey) {
            if ($permissionKey === $permission && is_string($moduleKey)) {
                return $moduleKey;
            }
        }

        $prefix = explode('.', $permission, 2)[0] ?? '';

        return $this->findModule($prefix) ? $prefix : null;
    }

    /**
     * @return array<int, list<string>> department_id => dashboard permission keys
     */
    public function dashboardPermissionsByDepartmentIds(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        $departments = Department::query()->whereIn('id', $departmentIds)->get();
        $map = [];

        foreach ($departments as $department) {
            $map[(int) $department->id] = $this->dashboardPermissionsForDepartment($department);
        }

        return $map;
    }
}
