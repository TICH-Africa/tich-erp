<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\RBACService;
use Illuminate\Support\Facades\DB;

class SidebarUserProfile
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $photoUrl = null,
        public string $initials = '?',
        public array $roles = [],
    ) {}

    public static function fromUser(?User $user): ?self
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing(['staff', 'student.applicant']);
        $roles = self::roleLabelsFor($user);

        $staff = $user->staff ?? ($user->staff_id ? Staff::query()->find($user->staff_id) : null);
        if ($staff) {
            return new self(
                name: $staff->fullName(),
                email: (string) $user->email,
                photoUrl: $staff->photoUrl(),
                initials: $staff->initials() ?: self::initialsFromName($staff->fullName()),
                roles: $roles,
            );
        }

        $student = $user->student ?? ($user->student_id ? Student::query()->with('applicant')->find($user->student_id) : null);
        if ($student) {
            $name = $student->displayName();

            return new self(
                name: $name,
                email: (string) $user->email,
                photoUrl: $student->photoUrl(),
                initials: $student->initials() ?: self::initialsFromName($name),
                roles: $roles,
            );
        }

        $name = $user->displayName();

        return new self(
            name: $name,
            email: (string) $user->email,
            photoUrl: null,
            initials: self::initialsFromName($name) ?: strtoupper(substr((string) $user->email, 0, 1)),
            roles: $roles,
        );
    }

    /**
     * @return list<string>
     */
    private static function roleLabelsFor(User $user): array
    {
        if (app(RBACService::class)->isPlatformAdministrator($user)
            && ! DB::table('user_roles')->where('user_id', $user->id)->exists()) {
            return ['Super Admin'];
        }

        return DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $user->id)
            ->where(function ($query) {
                $query->whereNull('ur.expires_at')
                    ->orWhere('ur.expires_at', '>', now());
            })
            ->orderBy('r.display_name')
            ->orderBy('r.role_name')
            ->get(['r.role_name', 'r.display_name'])
            ->map(fn ($row) => trim((string) ($row->display_name ?: $row->role_name)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return strtoupper(collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->join(''));
    }
}
