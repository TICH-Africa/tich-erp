<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentPortalService
{
    public function __construct(
        protected RBACService $rbacService,
        protected AuditService $auditService,
    ) {}

    /**
     * @param  array{username: string, password: string}  $credentials
     */
    public function activatePortalAccount(Student $student, array $credentials): User
    {
        if ($student->portal_activated_at !== null || $student->user_id !== null) {
            throw ValidationException::withMessages([
                'token' => 'This student portal has already been activated. Please sign in instead.',
            ]);
        }

        if ($student->portal_invite_expires_at && $student->portal_invite_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => 'This activation link has expired. Contact the admissions office for a new invite.',
            ]);
        }

        $student->loadMissing('applicant');

        if (! $student->applicant) {
            throw ValidationException::withMessages([
                'token' => 'Student record is incomplete. Contact the admissions office.',
            ]);
        }

        return DB::transaction(function () use ($student, $credentials) {
            $existingUser = User::query()->where('email', $student->applicant->email)->first();

            if ($existingUser && $existingUser->student_id && (int) $existingUser->student_id !== (int) $student->id) {
                throw ValidationException::withMessages([
                    'email' => 'An account already exists for this email address.',
                ]);
            }

            if ($existingUser) {
                $user = $existingUser;
                $user->update([
                    'username' => $credentials['username'],
                    'password_hash' => Hash::make($credentials['password']),
                    'user_type' => 'student',
                    'student_id' => $student->id,
                    'is_active' => 1,
                ]);
            } else {
                $user = User::create([
                    'username' => $credentials['username'],
                    'email' => $student->applicant->email,
                    'password_hash' => Hash::make($credentials['password']),
                    'user_type' => 'student',
                    'student_id' => $student->id,
                    'is_active' => 1,
                ]);
            }

            $this->ensureStudentRole($user);

            $student->update([
                'user_id' => $user->id,
                'enrollment_status' => 'active',
                'portal_activated_at' => now(),
                'portal_invite_token' => null,
                'portal_invite_expires_at' => null,
                'updated_at' => now(),
            ]);

            $this->auditService->log(
                'sis.portal.activated',
                'students',
                $student->id,
                null,
                [
                    'user_id' => $user->id,
                    'registration_number' => $student->registration_number,
                    'application_number' => $student->applicant->application_number,
                ],
                'Student portal account activated',
                'success',
                $user->id
            );

            Auth::login($user);

            return $user;
        });
    }

    public function studentForUser(User $user): ?Student
    {
        if ($user->student_id) {
            return Student::query()
                ->with(['applicant.documents', 'program.department', 'campus'])
                ->find($user->student_id);
        }

        return Student::query()
            ->with(['applicant.documents', 'program.department', 'campus'])
            ->where('user_id', $user->id)
            ->first();
    }

    private function ensureStudentRole(User $user): void
    {
        $studentRoleId = Role::query()->where('role_name', 'Student')->value('id');

        if (! $studentRoleId) {
            return;
        }

        $hasStudentRole = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $studentRoleId)
            ->exists();

        if (! $hasStudentRole) {
            $this->rbacService->assignRoleToUser($user, (int) $studentRoleId);
        }
    }
}
