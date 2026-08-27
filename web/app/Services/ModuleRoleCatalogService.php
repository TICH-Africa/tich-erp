<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ModuleRoleCatalogService
{
    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function modules(): array
    {
        $modules = collect(config('tich-module-roles.modules', []))
            ->mapWithKeys(fn (array $module, string $key) => [
                $key => [
                    'label' => $module['label'],
                    'description' => $module['description'] ?? '',
                ],
            ])
            ->all();

        return [
            config('tich-module-roles.institution_module_key', '_institution') => [
                'label' => 'Institution-wide',
                'description' => 'Cross-module executive and portal roles.',
            ],
        ] + $modules;
    }

    public function moduleLabel(?string $moduleKey): string
    {
        if ($moduleKey === null || $moduleKey === '') {
            return 'Institution-wide';
        }

        return $this->modules()[$moduleKey]['label'] ?? ucfirst(str_replace('_', ' ', $moduleKey));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function predefinedRolesForModule(string $moduleKey): array
    {
        return config("tich-module-roles.modules.{$moduleKey}.roles", []);
    }

    /**
     * Permission modules applicable when editing a role.
     *
     * @return list<string>
     */
    public function permissionModulesForRole(Role $role): array
    {
        if ($role->module_key) {
            $predefined = collect($this->predefinedRolesForModule($role->module_key))
                ->firstWhere('role_name', $role->role_name);

            if ($predefined) {
                return $predefined['permission_modules'] ?? [$role->module_key];
            }

            return array_values(array_unique(array_filter([
                $role->module_key,
                $role->module_key === 'admissions' ? 'admin' : null,
                in_array($role->module_key, ['finance', 'hr', 'procurement', 'qa', 'administration', 'research', 'ict', 'academics', 'monitoring_evaluation'], true) ? 'core' : null,
            ])));
        }

        return collect($role->permissions()->pluck('module'))->unique()->filter()->values()->all();
    }

    /**
     * @return Collection<int, object{id: int, slug: string, module: string, category: string, permission_name?: string}>
     */
    public function permissionsForRole(Role $role): Collection
    {
        $catalog = app(RbacCatalogService::class);
        $modules = $this->permissionModulesForRole($role);

        if ($catalog->hasDefinition($role->role_name) || Permission::query()->doesntExist()) {
            $rows = collect($catalog->permissions());

            if ($modules !== []) {
                $rows = $rows->whereIn('module', $modules);
            }

            return $rows
                ->sortBy(['module', 'slug'])
                ->values()
                ->map(fn (array $permission, int $index) => (object) [
                    'id' => $index + 1,
                    'slug' => $permission['slug'],
                    'module' => $permission['module'],
                    'category' => $permission['category'],
                    'permission_name' => $permission['permission_name'],
                ]);
        }

        if ($modules === []) {
            return Permission::query()->orderBy('module')->orderBy('slug')->get();
        }

        return Permission::query()
            ->whereIn('module', $modules)
            ->orderBy('module')
            ->orderBy('slug')
            ->get();
    }

    /**
     * @return list<array{action: string, label: string, categories: array<string, array{id: int, slug: string, label: string, checked: bool}>}>
     */
    public function permissionMatrixForRole(Role $role): array
    {
        $categoryLabels = config('tich-module-roles.permission_categories', []);
        $permissions = $this->permissionsForRole($role);
        $catalog = app(RbacCatalogService::class);
        $assignedIds = [];
        $assignedSlugs = [];

        if ($catalog->hasDefinition($role->role_name)) {
            $assignedSlugs = $catalog->grantedSlugSetForRole($role->role_name);
        } else {
            $assignedIds = $role->permissions()->pluck('permissions.id')->all();
        }

        $matrix = [];

        foreach ($permissions->groupBy(fn ($permission) => $this->actionFromSlug($permission->slug)) as $action => $group) {
            $categories = [];

            foreach ($group as $permission) {
                $checked = $assignedSlugs !== []
                    ? isset($assignedSlugs[$permission->slug])
                    : in_array((int) $permission->id, $assignedIds, true);

                $categories[$permission->category] = [
                    'id' => (int) $permission->id,
                    'slug' => $permission->slug,
                    'label' => $categoryLabels[$permission->category] ?? ucfirst($permission->category),
                    'checked' => $checked,
                ];
            }

            $matrix[] = [
                'action' => $action,
                'label' => $this->actionLabel($action),
                'module' => $group->first()->module,
                'categories' => $categories,
            ];
        }

        usort($matrix, fn ($a, $b) => [$a['module'], $a['label']] <=> [$b['module'], $b['label']]);

        return $matrix;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function syncPredefinedRolePermissions(Role $role, array $definition): void
    {
        $permissionIds = $this->permissionIdsFromDefinition($definition);

        $this->syncRolePermissionIds($role, $permissionIds);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<int>
     */
    public function permissionIdsFromDefinition(array $definition): array
    {
        $modules = $definition['permission_modules'] ?? [];
        $categories = $definition['permission_categories'] ?? [];
        $extraSlugs = $definition['extra_slugs'] ?? [];

        $query = Permission::query();

        if ($modules !== []) {
            $query->where(function ($builder) use ($modules, $categories) {
                $builder->whereIn('module', $modules);

                if ($categories !== []) {
                    $builder->whereIn('category', $categories);
                }
            });
        }

        $ids = $query->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($extraSlugs !== []) {
            $extraIds = Permission::query()->whereIn('slug', $extraSlugs)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ids = array_values(array_unique(array_merge($ids, $extraIds)));
        }

        return $ids;
    }

    /**
     * @param  list<int>  $permissionIds
     */
    public function syncRolePermissionIds(Role $role, array $permissionIds, ?int $grantedBy = null): void
    {
        $allowedIds = $this->permissionsForRole($role)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $permissionIds = array_values(array_intersect($permissionIds, $allowedIds));

        DB::table('role_permissions')->where('role_id', $role->id)->delete();

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $role->id,
                'permission_id' => $permissionId,
                'granted_at' => now(),
                'granted_by' => $grantedBy,
            ]);
        }
    }

    private function actionFromSlug(string $slug): string
    {
        $parts = explode('_', $slug);

        if (count($parts) < 3) {
            return $slug;
        }

        array_shift($parts);
        array_pop($parts);

        return implode('_', $parts);
    }

    private function actionLabel(string $action): string
    {
        return ucwords(str_replace('_', ' ', $action));
    }
}
