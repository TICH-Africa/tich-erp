<?php

namespace App\Console\Commands;

use App\Mail\BirthdayWishMail;
use App\Models\Staff;
use App\Models\Student;
use App\Support\ModuleMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendBirthdayWishes extends Command
{
    protected $signature = 'app:send-birthday-wishes {--dry-run : List recipients without sending}';

    protected $description = 'Email birthday wishes to staff/employees and students whose birthday is today';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = now();
        $month = (int) $today->month;
        $day = (int) $today->day;

        $recipients = [];

        foreach ($this->staffBirthdayPeople($month, $day) as $person) {
            $recipients[$person['email']] = $person;
        }

        foreach ($this->studentBirthdayPeople($month, $day) as $person) {
            if (! isset($recipients[$person['email']])) {
                $recipients[$person['email']] = $person;
            }
        }

        if ($recipients === []) {
            $this->info('No birthdays today.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $person) {
            if ($dryRun) {
                $this->line("Would send → {$person['name']} <{$person['email']}> ({$person['audience']})");
                $sent++;

                continue;
            }

            $result = ModuleMail::trySend(
                ModuleMail::NOTIFICATION,
                $person['email'],
                new BirthdayWishMail($person['name'], $person['audience'])
            );

            if ($result['sent']) {
                $sent++;
                $this->line("Sent → {$person['name']} <{$person['email']}>");
            } else {
                $failed++;
                $this->warn("Failed → {$person['name']} <{$person['email']}>: ".($result['error'] ?? 'unknown error'));
            }
        }

        $this->info(($dryRun ? 'Dry run: ' : '')."{$sent} birthday wish(es), {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<array{email: string, name: string, audience: string}>
     */
    private function staffBirthdayPeople(int $month, int $day): array
    {
        if (! Schema::hasTable('staff') || ! Schema::hasColumn('staff', 'date_of_birth')) {
            return [];
        }

        $query = Staff::query()
            ->with('user')
            ->whereNotNull('date_of_birth')
            ->whereRaw('MONTH(date_of_birth) = ? AND DAY(date_of_birth) = ?', [$month, $day])
            ->whereNotIn('employment_status', ['terminated', 'resigned', 'retired', 'deceased']);

        // Skip known placeholder DOB used for incomplete profiles.
        $query->whereDate('date_of_birth', '!=', '1990-01-01');

        if ($month === 2 && $day === 28 && ! $this->isLeapYear((int) now()->year)) {
            $feb29 = Staff::query()
                ->with('user')
                ->whereNotNull('date_of_birth')
                ->whereRaw('MONTH(date_of_birth) = 2 AND DAY(date_of_birth) = 29')
                ->whereNotIn('employment_status', ['terminated', 'resigned', 'retired', 'deceased'])
                ->whereDate('date_of_birth', '!=', '1990-01-01')
                ->get();
        } else {
            $feb29 = collect();
        }

        $people = [];

        foreach ($query->get()->concat($feb29) as $staff) {
            $email = $staff->resolveErpEmail($staff->user?->email);
            if (! $email) {
                continue;
            }

            $people[] = [
                'email' => strtolower($email),
                'name' => $staff->fullName() ?: 'Colleague',
                'audience' => 'staff and employee community',
            ];
        }

        return $people;
    }

    /**
     * @return list<array{email: string, name: string, audience: string}>
     */
    private function studentBirthdayPeople(int $month, int $day): array
    {
        if (! Schema::hasTable('students') || ! Schema::hasTable('applicants')) {
            return [];
        }

        if (! Schema::hasColumn('applicants', 'date_of_birth')) {
            return [];
        }

        $query = Student::query()
            ->with(['user', 'applicant'])
            ->where(function ($q) {
                $q->where('is_active', 1)->orWhereNull('is_active');
            })
            ->whereHas('applicant', function ($q) use ($month, $day) {
                $q->whereNotNull('date_of_birth')
                    ->whereRaw('MONTH(date_of_birth) = ? AND DAY(date_of_birth) = ?', [$month, $day]);
            });

        $students = $query->get();

        if ($month === 2 && $day === 28 && ! $this->isLeapYear((int) now()->year)) {
            $feb29 = Student::query()
                ->with(['user', 'applicant'])
                ->where(function ($q) {
                    $q->where('is_active', 1)->orWhereNull('is_active');
                })
                ->whereHas('applicant', function ($q) {
                    $q->whereNotNull('date_of_birth')
                        ->whereRaw('MONTH(date_of_birth) = 2 AND DAY(date_of_birth) = 29');
                })
                ->get();
            $students = $students->concat($feb29);
        }

        $people = [];

        foreach ($students as $student) {
            $email = $this->resolveStudentEmail($student);
            if (! $email) {
                continue;
            }

            $people[] = [
                'email' => strtolower($email),
                'name' => $student->displayName() ?: 'Student',
                'audience' => 'student community',
            ];
        }

        return $people;
    }

    private function resolveStudentEmail(Student $student): ?string
    {
        $candidates = [
            $student->user?->email,
            $student->applicant?->email,
        ];

        foreach ($candidates as $value) {
            $email = is_string($value) ? strtolower(trim($value)) : '';
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }

    private function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
