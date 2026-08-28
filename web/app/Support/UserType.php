<?php

namespace App\Support;

final class UserType
{
    public const STUDENT = 'student';

    public const STAFF = 'staff';

    public const ADMIN = 'admin';

    public const EXTERNAL = 'external';

    public const SUPER_ADMIN = 'super_admin';

  /** @var list<string> */
    public const ALL = [
        self::STUDENT,
        self::STAFF,
        self::ADMIN,
        self::EXTERNAL,
        self::SUPER_ADMIN,
    ];

    /**
     * Employee directory accounts (HR staff list / ICT employee tab).
     *
     * @var list<string>
     */
    public const EMPLOYEE_ACCOUNT_TYPES = [
        self::STAFF,
        self::ADMIN,
        self::EXTERNAL,
    ];

    public static function label(string $type): string
    {
        return match ($type) {
            self::STUDENT => 'Student',
            self::STAFF => 'Staff',
            self::ADMIN => 'Admin',
            self::EXTERNAL => 'External',
            self::SUPER_ADMIN => 'Super admin',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public static function isEmployeeAccount(string $type): bool
    {
        return in_array($type, self::EMPLOYEE_ACCOUNT_TYPES, true);
    }

    public static function isSuperAdmin(string $type): bool
    {
        return $type === self::SUPER_ADMIN;
    }

    public static function isPlatformOperator(string $type): bool
    {
        return $type === self::SUPER_ADMIN || $type === self::ADMIN;
    }
}
