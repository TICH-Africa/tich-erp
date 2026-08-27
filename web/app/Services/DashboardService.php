<?php

namespace App\Services;

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

    /**
     * @deprecated Direct user permission grants were removed with the permissions tables.
     * @return array<string, string>
     */
    public function modulePermissionIds(array $permissionKeys): array
    {
        return [];
    }

    /**
     * @deprecated Direct user permission grants were removed with the permissions tables.
     * @return list<string>
     */
    public function userDirectModulePermissions(User $user): array
    {
        return [];
    }
}
