<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Code-owned RBAC catalog (permissions, categories, system/module roles).
 * Runtime permission checks for predefined roles resolve here — not from seeded DB rows.
 * Only thin `roles` rows are materialized so `user_roles.role_id` FKs work.
 */
class RbacCatalogService
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $roleDefinitionsByName = null;

    /** @var list<string>|null */
    private ?array $allSlugs = null;

    /** @var array<string, array<string, true>>|null */
    private ?array $grantedSlugSets = null;

    /**
     * @return list<array{category_code: string, category_name: string, description?: string, display_order: int, is_system: bool}>
     */
    public function categories(): array
    {
        return config('tich-role-categories', []);
    }

    /**
     * @return array<string, string> code => name
     */
    public function categoryOptions(): array
    {
        return collect($this->categories())
            ->sortBy('display_order')
            ->mapWithKeys(fn (array $category) => [
                $category['category_code'] => $category['category_name'],
            ])
            ->all();
    }

    /**
     * @return list<array{slug: string, permission_name: string, module: string, category: string, description: string}>
     */
    public function permissions(): array
    {
        $categories = config('tich-permissions.categories', []);
        $modules = config('tich-permissions.modules', []);
        $rows = [];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                foreach ($categories as $category) {
                    $rows[] = [
                        'permission_name' => "{$module}.{$action}.{$category}",
                        'slug' => "{$module}_{$action}_{$category}",
                        'module' => $module,
                        'category' => $category,
                        'description' => "Permission to {$category} {$action} in {$module} module",
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function allSlugs(): array
    {
        if ($this->allSlugs !== null) {
            return $this->allSlugs;
        }

        return $this->allSlugs = array_column($this->permissions(), 'slug');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function roleDefinitionsByName(): array
    {
        if ($this->roleDefinitionsByName !== null) {
            return $this->roleDefinitionsByName;
        }

        $definitions = [];

        foreach (config('tich-module-roles.modules', []) as $moduleKey => $moduleConfig) {
            foreach ($moduleConfig['roles'] ?? [] as $definition) {
                $name = $definition['role_name'] ?? null;

                if (! $name) {
                    continue;
                }

                $institutionKey = config('tich-module-roles.institution_module_key', '_institution');

                $definitions[$name] = array_merge($definition, [
                    // Institution-wide catalog roles are stored with null module_key.
                    'module_key' => $moduleKey === $institutionKey ? null : $moduleKey,
                ]);
            }
        }

        return $this->roleDefinitionsByName = $definitions;
    }

    public function hasDefinition(string $roleName): bool
    {
        return isset($this->roleDefinitionsByName()[$roleName]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definitionForRole(string $roleName): ?array
    {
        return $this->roleDefinitionsByName()[$roleName] ?? null;
    }

    public function roleGrantsSlug(string $roleName, string $slug): bool
    {
        $definition = $this->definitionForRole($roleName);

        if (! $definition) {
            return false;
        }

        if (! empty($definition['grants_all'])) {
            return true;
        }

        return isset($this->grantedSlugSetForRole($roleName)[$slug]);
    }

    /**
     * @return array<string, true>
     */
    public function grantedSlugSetForRole(string $roleName): array
    {
        if (isset($this->grantedSlugSets[$roleName])) {
            return $this->grantedSlugSets[$roleName];
        }

        $definition = $this->definitionForRole($roleName);

        if (! $definition) {
            return $this->grantedSlugSets[$roleName] = [];
        }

        if (! empty($definition['grants_all'])) {
            $set = array_fill_keys($this->allSlugs(), true);

            return $this->grantedSlugSets[$roleName] = $set;
        }

        $modules = $definition['permission_modules'] ?? [];
        $categories = $definition['permission_categories'] ?? [];
        $extra = $definition['extra_slugs'] ?? [];
        $set = [];

        foreach ($this->permissions() as $permission) {
            if ($modules !== [] && ! in_array($permission['module'], $modules, true)) {
                continue;
            }

            if ($categories !== [] && ! in_array($permission['category'], $categories, true)) {
                continue;
            }

            $set[$permission['slug']] = true;
        }

        foreach ($extra as $slug) {
            $set[$slug] = true;
        }

        return $this->grantedSlugSets[$roleName] = $set;
    }

    /**
     * Materialize thin role rows for FK assignment only (no permission seeding).
     * Catalog in config/tich-module-roles.php is the source of truth; obsolete rows are removed.
     */
    public function ensureRolesExist(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $definitions = $this->roleDefinitionsByName();
        $catalogNames = array_keys($definitions);

        foreach ($definitions as $roleName => $definition) {
            Role::query()->updateOrCreate(
                ['role_name' => $roleName],
                [
                    'display_name' => $definition['display_name'] ?? $roleName,
                    'role_category' => $definition['role_category'] ?? 'administrative',
                    'description' => $definition['description'] ?? null,
                    'module_key' => $definition['module_key'] ?? null,
                    'is_system_role' => true,
                ]
            );
        }

        $replacements = config('tich-module-roles.role_replacements', []);
        $retired = array_values(array_unique(array_merge(
            array_keys($replacements),
            config('tich-module-roles.retired_roles', [])
        )));

        $obsolete = Role::query()
            ->whereNotIn('role_name', $catalogNames)
            ->where(function ($query) use ($retired) {
                $query->where('is_system_role', true);

                if ($retired !== []) {
                    $query->orWhereIn('role_name', $retired);
                }
            })
            ->get();

        foreach ($obsolete as $role) {
            $this->purgeObsoleteRole($role, $replacements[$role->role_name] ?? null);
        }

        // Catalog roles resolve permissions from config — clear leftover dynamic pivots.
        if (Schema::hasTable('role_permissions') && $catalogNames !== []) {
            $catalogRoleIds = Role::query()
                ->whereIn('role_name', $catalogNames)
                ->pluck('id');

            if ($catalogRoleIds->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('role_id', $catalogRoleIds)->delete();
            }
        }
    }

    /**
     * Number of permission slugs granted by the hardcoded catalog (not DB pivots).
     */
    public function catalogPermissionCount(string $roleName): int
    {
        if (! $this->hasDefinition($roleName)) {
            return 0;
        }

        return count($this->grantedSlugSetForRole($roleName));
    }

    /**
     * Remove a former catalog role; optionally migrate user assignments to a replacement.
     */
    private function purgeObsoleteRole(Role $role, ?string $replacementName): void
    {
        $roleId = (int) $role->id;
        $replacementId = $replacementName
            ? Role::query()->where('role_name', $replacementName)->value('id')
            : null;

        if ($replacementId && Schema::hasTable('user_roles')) {
            $assignments = DB::table('user_roles')->where('role_id', $roleId)->get();

            foreach ($assignments as $assignment) {
                $exists = DB::table('user_roles')
                    ->where('user_id', $assignment->user_id)
                    ->where('role_id', $replacementId)
                    ->where(function ($query) use ($assignment) {
                        if (property_exists($assignment, 'campus_id')) {
                            $query->where('campus_id', $assignment->campus_id);
                        }
                        if (property_exists($assignment, 'department_id')) {
                            $query->where('department_id', $assignment->department_id);
                        }
                    })
                    ->exists();

                if (! $exists) {
                    DB::table('user_roles')->where('id', $assignment->id)->update([
                        'role_id' => $replacementId,
                    ]);
                }
            }
        }

        if (Schema::hasTable('user_roles')) {
            DB::table('user_roles')->where('role_id', $roleId)->delete();
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('role_id', $roleId)->delete();
        }

        $role->delete();
    }

    public function roleIdByName(string $roleName): ?int
    {
        $this->ensureRolesExist();

        $id = Role::query()->where('role_name', $roleName)->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Public site navigation from code (config/tich-navigation.php).
     *
     * @return array{header: list<array>, footer_primary: list<array>, footer_quick_links: list<array>, contact: list<array>, social: list<array>}
     */
    public function navigation(): array
    {
        return [
            'header' => config('tich-navigation.header', []),
            'footer_primary' => config('tich-navigation.footer_primary', []),
            'footer_quick_links' => config('tich-navigation.footer_quick_links', []),
            'contact' => config('tich-navigation.contact', []),
            'social' => config('tich-navigation.social', []),
        ];
    }

    /**
     * One-shot cache bust helper for long-lived workers (optional).
     */
    public function forgetCachedCatalog(): void
    {
        $this->roleDefinitionsByName = null;
        $this->allSlugs = null;
        $this->grantedSlugSets = null;
        Cache::forget('tich.rbac.catalog.version');
    }
}
