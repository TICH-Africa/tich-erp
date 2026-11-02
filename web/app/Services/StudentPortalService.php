<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentPortalService
{
  /** @var list<string> */
  private const STUDENT_ROLE_NAMES = ['Student', 'Applicant', 'Alumni'];

  public function __construct(
    protected RBACService $rbacService,
    protected AuditService $auditService,
  ) {}

  /**
   * Link an admitted applicant's student record to an existing login (same email) after fee payment.
   * Applicants are students - staff/lecturer roles from ERP invites are removed.
   */
  public function ensureStudentAccountForApplicant(Applicant $applicant, Student $student): ?User
  {
    $applicant->loadMissing('program');
    $student->loadMissing('applicant');

    $email = strtolower(trim((string) $applicant->email));
    if ($email === '') {
      return null;
    }

    if ($student->portal_activated_at !== null && $student->user_id !== null) {
      $user = User::query()->find($student->user_id);

      return $user?->isEnrolledStudent() ? $user : null;
    }

    $user = User::query()->where('email', $email)->first();
    if (! $user) {
      return null;
    }

    if ($user->student_id && (int) $user->student_id !== (int) $student->id) {
      return null;
    }

    return DB::transaction(function () use ($user, $student, $applicant) {
      $this->applyStudentIdentity($user, $student, markPortalActivated: true);

      $this->auditService->log(
        'sis.student.account_linked',
        'students',
        $student->id,
        null,
        [
          'user_id' => $user->id,
          'application_number' => $applicant->application_number,
          'registration_number' => $student->registration_number,
        ],
        'Existing login linked to admitted student record',
        'success',
        $user->id
      );

      return $user->fresh();
    });
  }

  /**
   * @param  array{password: string}  $credentials
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

    // An invite is enough to open the portal: the rest of the record can be filled in later.
    $email = trim((string) ($student->email ?: $student->applicant?->email));

    if ($email === '') {
      throw ValidationException::withMessages([
        'token' => 'No email address is on your student record yet. Contact the admissions office to add one.',
      ]);
    }

    return DB::transaction(function () use ($student, $credentials, $email) {
      $existingUser = User::query()->where('email', $email)->first();

      if ($existingUser && $existingUser->student_id && (int) $existingUser->student_id !== (int) $student->id) {
        throw ValidationException::withMessages([
          'email' => 'An account already exists for this email address.',
        ]);
      }

      if ($existingUser) {
        $user = $existingUser;
        $user->update([
          'password_hash' => Hash::make($credentials['password']),
          'is_active' => 1,
        ]);
      } else {
        $user = User::create([
          'email' => $email,
          'password_hash' => Hash::make($credentials['password']),
          'user_type' => 'student',
          'student_id' => $student->id,
          'is_active' => 1,
        ]);
      }

      $this->applyStudentIdentity($user, $student, markPortalActivated: true);

      $this->auditService->log(
        'sis.portal.activated',
        'students',
        $student->id,
        null,
        [
          'user_id' => $user->id,
          'registration_number' => $student->registration_number,
          'application_number' => $student->applicant?->application_number,
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

  private function applyStudentIdentity(User $user, Student $student, bool $markPortalActivated): void
  {
    $user->forceFill([
      'user_type' => 'student',
      'student_id' => $student->id,
      'is_active' => 1,
    ])->save();

    $this->stripNonStudentRoles($user);
    $this->ensureStudentRole($user);
    $this->unlinkProvisionalStaffIfLinked($user);

    $studentUpdates = [
      'user_id' => $user->id,
      'updated_at' => now(),
    ];

    // A graduate keeps their status: joining the portal after convocation must not put them back
    // on the current student list that Admissions just cleared.
    if (! in_array($student->enrollment_status, AlumniService::STATUSES, true)) {
      $studentUpdates['enrollment_status'] = 'active';
    }

    if ($markPortalActivated) {
      $studentUpdates['portal_activated_at'] = now();
      $studentUpdates['portal_invite_token'] = null;
      $studentUpdates['portal_invite_expires_at'] = null;
    }

    $student->update($studentUpdates);
  }

  private function stripNonStudentRoles(User $user): void
  {
    $studentRoleIds = Role::query()
      ->whereIn('role_name', self::STUDENT_ROLE_NAMES)
      ->pluck('id')
      ->map(fn ($id) => (int) $id)
      ->all();

    $assignedRoleIds = DB::table('user_roles')
      ->where('user_id', $user->id)
      ->pluck('role_id')
      ->map(fn ($id) => (int) $id);

    foreach ($assignedRoleIds as $roleId) {
      if (in_array($roleId, $studentRoleIds, true)) {
        continue;
      }

      $this->rbacService->revokeRoleFromUser($user, $roleId, $user->id);
    }
  }

  private function unlinkProvisionalStaffIfLinked(User $user): void
  {
    if (! $user->staff_id) {
      return;
    }

    $staff = Staff::query()->find($user->staff_id);
    if (! $staff || ! $this->isProvisionalInviteStaff($staff)) {
      return;
    }

    $user->forceFill(['staff_id' => null])->save();

    if ((int) $staff->user_id === (int) $user->id) {
      $staff->update(['user_id' => null]);
    }
  }

  private function isProvisionalInviteStaff(Staff $staff): bool
  {
    return $staff->employment_status === 'onboarding'
      && strcasecmp((string) $staff->first_name, 'Pending') === 0
      && strcasecmp((string) $staff->surname, 'Invitee') === 0;
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
