<?php

namespace App\Services;

use App\Mail\StudentPortalInvitationEmail;
use App\Models\Applicant;
use App\Models\Student;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StudentPortalInviteService
{
    /**
     * The list the ICT desk works from, in the words the table's own badges use.
     *
     * @var array<string, string>
     */
    public const FILTERS = [
        'waiting' => 'Students waiting to activate',
        'pending' => 'Awaiting activation',
        'active' => 'Active',
        'expired' => 'Expired link',
        'none' => 'No invite yet',
        'inactive' => 'Inactive students',
        'all' => 'Everyone',
    ];

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
                'message' => $this->unknownEmailMessage($email),
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
    public function browse(string $search, string $filter, int $limit = 60): Collection
    {
        $query = $this->browseQuery();

        if ($search !== '') {
            $this->applyTerm($query, $search);
        }

        $this->applyFilter($query, $filter);

        // The plain list is the activation queue, so the link that dies soonest leads.
        // A search is a lookup, so it comes back in the order records were made.
        $query = $search === ''
            ? $query->orderByRaw('COALESCE(students.portal_invite_expires_at, students.created_at) DESC')
            : $query->orderByDesc('students.id');

        return $query->limit($limit)->get();
    }

    /**
     * @return array<string, int>
     */
    public function filterCounts(): array
    {
        $account = '(students.user_id is not null or students.portal_activated_at is not null)';
        $noAccount = '(students.user_id is null and students.portal_activated_at is null)';
        $invited = 'students.portal_invite_token is not null';
        $live = '(students.portal_invite_expires_at is null or students.portal_invite_expires_at >= now())';

        $row = $this->browseQuery()
            ->selectRaw("
                sum(students.is_active and {$noAccount}) as waiting,
                sum(students.is_active and {$noAccount} and {$invited} and {$live}) as pending,
                sum(students.is_active and {$account}) as active,
                sum(students.is_active and {$noAccount} and {$invited} and students.portal_invite_expires_at < now()) as expired,
                sum(students.is_active and {$noAccount} and students.portal_invite_token is null) as none,
                sum(students.is_active = 0) as inactive,
                count(*) as every
            ")
            ->first();

        return collect(self::FILTERS)
            ->mapWithKeys(fn (string $label, string $key) => [
                $key => (int) ($row->{($key === 'all' ? 'every' : $key)} ?? 0),
            ])
            ->all();
    }

    private function browseQuery(): Builder
    {
        return Student::query()->with(['applicant', 'program.department', 'campus', 'user']);
    }

    private function applyFilter(Builder $query, string $filter): void
    {
        $hasAccount = fn (Builder $q) => $q
            ->whereNotNull('students.user_id')
            ->orWhereNotNull('students.portal_activated_at');
        $noAccount = fn (Builder $q) => $q
            ->whereNull('students.user_id')
            ->whereNull('students.portal_activated_at');

        match ($filter) {
            'waiting' => $query->where('students.is_active', 1)->where($noAccount),
            'active' => $query->where('students.is_active', 1)->where($hasAccount),
            'pending' => $query
                ->where('students.is_active', 1)
                ->where($noAccount)
                ->whereNotNull('students.portal_invite_token')
                ->where(fn (Builder $q) => $q->whereNull('students.portal_invite_expires_at')
                    ->orWhere('students.portal_invite_expires_at', '>=', now())),
            'expired' => $query
                ->where('students.is_active', 1)
                ->where($noAccount)
                ->whereNotNull('students.portal_invite_token')
                ->where('students.portal_invite_expires_at', '<', now()),
            'none' => $query
                ->where('students.is_active', 1)
                ->where($noAccount)
                ->whereNull('students.portal_invite_token'),
            'inactive' => $query->where('students.is_active', 0),
            default => null,
        };
    }

    private function applyTerm(Builder $query, string $term): void
    {
        $query->search($term);
    }

    /**
     * An email typed at the desk has to be answered by whichever record carries it. A graduate
     * still needs the portal for a transcript, and refusing them as "not registered" sent the
     * officer off to hunt for a student who was in the system all along.
     */
    private function findStudentByEmail(string $email): ?Student
    {
        return $this->browseQuery()
            ->where(function (Builder $query) use ($email): void {
                $query->whereRaw('LOWER(students.email) = ?', [$email])
                    ->orWhereHas('applicant', fn (Builder $applicant) => $applicant->whereRaw('LOWER(email) = ?', [$email]))
                    ->orWhereHas('user', fn (Builder $user) => $user->whereRaw('LOWER(users.email) = ?', [$email]));
            })
            ->orderByRaw('students.is_active DESC')
            ->orderByDesc('students.id')
            ->first();
    }

    /**
     * Where the address was seen, in the words an officer can act on, rather than a flat "not
     * in the system" for a person the school already holds.
     */
    private function unknownEmailMessage(string $email): string
    {
        $applicant = Applicant::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->orderByDesc('id')
            ->first();

        if ($applicant) {
            return trim($applicant->first_name.' '.$applicant->surname).' applied as '.$applicant->application_number
                .' on '.$applicant->created_at?->format('d M Y').', but Admissions has not admitted that application yet, so there is no student record to invite.';
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            return 'That email belongs to a portal login that is not linked to a student record yet. Open the account from the list below rather than inviting it again.';
        }

        return 'Nothing in the school carries '.$email.'. Check the spelling with the student, or add them under Admissions first.';
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
