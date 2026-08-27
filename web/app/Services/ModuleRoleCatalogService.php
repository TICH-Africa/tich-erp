<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Collection;

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
     * Permission modules applicable when viewing a role.
     *
     * @return list<string>
     */
    public function permissionModulesForRole(Role $role): array
    {
        $catalog = app(RbacCatalogService::class);

        if ($catalog->hasDefinition($role->role_name)) {
            $definition = $catalog->definitionForRole($role->role_name) ?? [];

            if (! empty($definition['grants_all'])) {
                return [];
            }

            return array_values($definition['permission_modules'] ?? []);
        }

        return $catalog->permissionModulesForModuleKey($role->module_key);
    }

    /**
     * @return Collection<int, object{id: int, slug: string, module: string, category: string, permission_name?: string}>
     */
    public function permissionsForRole(Role $role): Collection
    {
        $catalog = app(RbacCatalogService::class);
        $modules = $this->permissionModulesForRole($role);
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

    /**
     * @return list<array{action: string, label: string, categories: array<string, array{id: int, slug: string, label: string, checked: bool}>}>
     */
    public function permissionMatrixForRole(Role $role): array
    {
        $categoryLabels = config('tich-module-roles.permission_categories', []);
        $permissions = $this->permissionsForRole($role);
        $catalog = app(RbacCatalogService::class);
        $assignedSlugs = $catalog->grantedSlugSetForRoleRecord($role->role_name, $role->module_key);

        $matrix = [];

        foreach ($permissions->groupBy(fn ($permission) => $this->actionFromSlug($permission->slug)) as $action => $group) {
            $categories = [];

            foreach ($group as $permission) {
                $categories[$permission->category] = [
                    'id' => (int) $permission->id,
                    'slug' => $permission->slug,
                    'label' => $categoryLabels[$permission->category] ?? ucfirst($permission->category),
                    'checked' => isset($assignedSlugs[$permission->slug]),
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
