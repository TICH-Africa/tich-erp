<?php

namespace App\Console\Commands;

use App\Mail\WorkAnniversaryMail;
use App\Models\Staff;
use App\Services\LeaveAccrualService;
use App\Support\ModuleMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendWorkAnniversaryWishes extends Command
{
    protected $signature = 'hr:send-work-anniversary-wishes {--dry-run : List recipients without sending}';

    protected $description = 'Email work anniversary congratulations to active staff on each year since their first TICH contract start';

    public function __construct(
        protected LeaveAccrualService $leaveAccrual,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! Schema::hasTable('staff')) {
            $this->warn('Staff table not found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $today = now()->startOfDay();
        $month = (int) $today->month;
        $day = (int) $today->day;

        $candidates = $this->staffWithAnniversaryToday($month, $day);

        if ($month === 2 && $day === 28 && ! $this->isLeapYear((int) $today->year)) {
            $candidates = $candidates->concat($this->staffWithAnniversaryToday(2, 29));
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($candidates->unique('id') as $staff) {
            $start = $this->leaveAccrual->resolveEmploymentStart($staff);
            if (! $start) {
                $skipped++;

                continue;
            }

            if (! $this->isAnniversaryCalendarDay($start, $today)) {
                $skipped++;

                continue;
            }

            $yearsCompleted = (int) $start->diffInYears($today);
            if ($yearsCompleted < 1) {
                $skipped++;

                continue;
            }

            $email = $staff->resolveErpEmail($staff->user?->email);
            if (! $email) {
                $skipped++;
                $this->warn("No email for staff #{$staff->id} ({$staff->fullName()})");

                continue;
            }

            if ($dryRun) {
                $this->line("Would send → {$staff->fullName()} <{$email}> ({$yearsCompleted} year(s), since {$start->toDateString()})");
                $sent++;

                continue;
            }

            $result = ModuleMail::trySend(
                ModuleMail::NOTIFICATION,
                $email,
                new WorkAnniversaryMail(
                    $staff->fullName() ?: 'Colleague',
                    $yearsCompleted,
                    $start,
                ),
            );

            if ($result['sent']) {
                $sent++;
                $this->line("Sent → {$staff->fullName()} <{$email}> ({$yearsCompleted} year(s))");
            } else {
                $failed++;
                $this->warn("Failed → {$staff->fullName()} <{$email}>: ".($result['error'] ?? 'unknown error'));
            }
        }

        if ($sent === 0 && $failed === 0 && $skipped === 0) {
            $this->info('No work anniversaries today.');
        } else {
            $this->info(
                ($dryRun ? 'Dry run: ' : '')
                ."{$sent} anniversary message(s), {$failed} failed, {$skipped} skipped."
            );
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Staff still employed at TICH whose employment anchor (earliest contract or HR start date) matches today's month/day.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Staff>
     */
    private function staffWithAnniversaryToday(int $month, int $day)
    {
        $anchor = $this->employmentStartSql();

        return Staff::query()
            ->with('user')
            ->whereNotIn('employment_status', ['terminated', 'resigned', 'retired', 'deceased'])
            ->whereRaw("{$anchor} IS NOT NULL")
            ->whereRaw("MONTH({$anchor}) = ? AND DAY({$anchor}) = ?", [$month, $day])
            ->get();
    }

    private function employmentStartSql(): string
    {
        if (! Schema::hasTable('staff_contracts')) {
            return 'staff.employment_start_date';
        }

        return 'COALESCE(
            (SELECT MIN(start_date) FROM staff_contracts WHERE staff_contracts.staff_id = staff.id AND start_date IS NOT NULL),
            staff.employment_start_date
        )';
    }

    private function isAnniversaryCalendarDay(\Carbon\Carbon $start, \Carbon\Carbon $today): bool
    {
        if ($start->month === $today->month && $start->day === $today->day) {
            return true;
        }

        return $start->month === 2
            && $start->day === 29
            && $today->month === 2
            && $today->day === 28
            && ! $this->isLeapYear((int) $today->year);
    }

    private function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
