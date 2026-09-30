<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ExecutivePortal
{
    public const ROLE_CIA = 'Chief Institution Administrator';

    public const ROLE_CEO = 'CEO';

    /**
     * Observer: can open the executive portal but cannot mutate.
     */
    public static function isReadOnly(?User $user = null): bool
    {
        $user ??= Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->hasAnyRole([self::ROLE_CEO, 'Super Admin'])) {
            return false;
        }

        return $user->hasRole(self::ROLE_CIA);
    }

    public static function canAccessCeoPortal(?User $user = null): bool
    {
        $user ??= Auth::user();

        return (bool) $user?->hasAnyRole([self::ROLE_CEO, 'Super Admin', self::ROLE_CIA]);
    }

    public static function canAccessInstitutionAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();

        return (bool) $user?->hasAnyRole([self::ROLE_CIA, 'Super Admin']);
    }
}
