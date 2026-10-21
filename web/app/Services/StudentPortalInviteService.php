<?php

namespace App\Services;

use App\Mail\StudentPortalInvitationEmail;
use App\Models\Student;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StudentPortalInviteService
{
    public function __construct(
        protected AuditService $auditService,
        protected StudentEnrollmentService $enrollment,
    ) {}

    /**
     * @return array{success: bool, message: string, student?: Student}
     */
    public function sendToEmail(string $email, User $invitedBy): array
    {
        $email = strtolower(trim($email));

        $student = $this->findStudentByEmail($email);

        if (! $student) {
            return [
                'success' => false,
                'message' => 'No enrolled student is registered with '.$email.'. Admissions creates student records on admission, so ask them to confirm the email on the application.',
            ];
        }

        return $this->sendForStudent($student, $invitedBy);
    }

    /**
     * @return array{success: bool, message: string, student?: Student}
     */
    public function sendForStudent(Student $student, User $invitedBy): array
    {
        $student->loadMissing(['applicant', 'program.department', 'campus', 'user']);

        $recipient = $this->emailFor($student);

        if ($recipient === null) {
            return [
                'success' => false,
                'message' => 'Add an email address on '.$student->displayName()." ({$student->registration_number}) before sending the portal invite.",
            ];
        }

        if ($student->user_id !== null || $student->portal_activated_at !== null) {
            return [
                'success' => false,
                'message' => $student->displayName()." already has a student portal account. They can sign in or use forgot password on {$recipient}.",
            ];
        }

        // Mirrors the staff invite: every send issues a fresh link, so the previous one dies with it.
        $student = $this->enrollment->refreshPortalInvite($student);

        $deliveryModule = (string) config('tich-mail.invite_delivery_module', ModuleMail::NOTIFICATION);
        if ($deliveryModule === '' || ! config()->has("tich-mail.modules.{$deliveryModule}")) {
            $deliveryModule = ModuleMail::NOTIFICATION;
        }

        $delivery = ModuleMail::trySend(
            $deliveryModule,
            $recipient,
            new StudentPortalInvitationEmail($student, $deliveryModule),
        );

        $this->audit(
            $delivery['sent'] ? 'ict.student_portal_invite.sent' : 'ict.student_portal_invite.mail_failed',
            $student,
            [
                'email' => $recipient,
                'registration_number' => $student->registration_number,
                'delivery_module' => $deliveryModule,
                'error' => $delivery['error'],
            ],
            $delivery['sent'] ? 'success' : 'failure',
            $invitedBy->id,
        );

        if (! $delivery['sent']) {
            return [
                'success' => false,
                'message' => 'Portal invite could not be sent to '.$recipient.'. '.($delivery['error'] ?? 'Check the notification mailbox credentials in .env, then run php artisan config:clear.'),
            ];
        }

        return [
            'success' => true,
            'message' => 'Portal invite sent to '.$recipient.' for '.$student->displayName()." ({$student->registration_number}). The link expires on ".$student->portal_invite_expires_at?->format('j F Y').'.',
            'student' => $student,
        ];
    }

    /**
     * @return Collection<int, Student>
     */
    public function pendingActivations(int $limit = 60): Collection
    {
        return $this->studentQuery()
            ->whereNull('students.user_id')
            ->whereNull('students.portal_activated_at')
            ->orderByRaw('COALESCE(students.portal_invite_expires_at, students.created_at) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Student>
     */
    public function search(string $term, int $limit = 60): Collection
    {
        $term = trim($term);
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $this->studentQuery()
            ->where(function ($query) use ($like): void {
                $query
                    ->where('students.registration_number', 'like', $like)
                    ->orWhere('students.first_name', 'like', $like)
                    ->orWhere('students.surname', 'like', $like)
                    ->orWhere('students.email', 'like', $like)
                    ->orWhereHas('applicant', function ($applicant) use ($like): void {
                        $applicant->where('first_name', 'like', $like)
                            ->orWhere('surname', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('user', fn ($user) => $user->where('users.email', 'like', $like));
            })
            ->orderByDesc('students.id')
            ->limit($limit)
            ->get();
    }

    private function studentQuery(): Builder
    {
        return Student::query()
            ->with(['applicant', 'program.department', 'campus', 'user'])
            ->where('students.is_active', 1);
    }

    private function findStudentByEmail(string $email): ?Student
    {
        return $this->studentQuery()
            ->where(function ($query) use ($email): void {
                $query->whereRaw('LOWER(students.email) = ?', [$email])
                    ->orWhereHas('applicant', fn ($applicant) => $applicant->whereRaw('LOWER(email) = ?', [$email]))
                    ->orWhereHas('user', fn ($user) => $user->whereRaw('LOWER(users.email) = ?', [$email]));
            })
            ->orderByDesc('students.id')
            ->first();
    }

    private function emailFor(Student $student): ?string
    {
        foreach ([$student->email, $student->applicant?->email, $student->user?->email] as $candidate) {
            $candidate = strtolower(trim((string) $candidate));
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function audit(string $action, Student $student, array $payload, string $status, int $userId): void
    {
        try {
            $this->auditService->log(
                $action,
                'students',
                $student->id,
                null,
                $payload,
                null,
                $status,
                $userId,
            );
        } catch (\Throwable $e) {
            Log::warning('Student portal invite audit failed', [
                'action' => $action,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
