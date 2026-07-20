<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(protected RBACService $rbacService) {}

    public function modulesForUser(User $user): Collection
    {
        return collect(config('tich-dashboards.modules', []))
            ->filter(fn ($module) => $this->rbacService->hasPermission($user, $module['permission']))
            ->values();
    }

    public function allModules(): Collection
    {
        return collect(config('tich-dashboards.modules', []));
    }

    public function modulePermissionIds(array $permissionKeys): array
    {
        $slugs = collect($permissionKeys)->map(function ($key) {
            $aliases = array_merge(
                config('tich.permission_aliases', []),
                config('tich-dashboards.permission_aliases', [])
            );

            return $aliases[$key] ?? str_replace(['.', ':', '-'], '_', $key);
        });

        return Permission::query()
            ->whereIn('slug', $slugs->all())
            ->pluck('id', 'slug')
            ->all();
    }

    public function userDirectModulePermissions(User $user): array
    {
        return $user->permissions()
            ->pluck('permissions.slug')
            ->all();
    }
}
