<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\Student;
use App\Models\User;

class SidebarUserProfile
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $photoUrl = null,
        public string $initials = '?',
    ) {}

    public static function fromUser(?User $user): ?self
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing(['staff', 'student.applicant']);

        $staff = $user->staff ?? ($user->staff_id ? Staff::query()->find($user->staff_id) : null);
        if ($staff) {
            return new self(
                name: $staff->fullName(),
                email: (string) $user->email,
                photoUrl: $staff->photoUrl(),
                initials: $staff->initials() ?: self::initialsFromName($staff->fullName()),
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
            );
        }

        $name = $user->displayName();

        return new self(
            name: $name,
            email: (string) $user->email,
            photoUrl: null,
            initials: self::initialsFromName($name) ?: strtoupper(substr((string) $user->email, 0, 1)),
        );
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
