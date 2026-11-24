<?php

namespace App\Console\Commands;

use App\Mail\WeeklyTimeLogReminderMail;
use App\Services\PlatformNotificationService;
use App\Services\WeeklyTimeLogService;
use App\Support\ModuleMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RemindWeeklyTimeLogs extends Command
{
    protected $signature = 'hr:remind-weekly-time-logs {--dry-run : List recipients without sending} {--force : Run even outside the 20th-to-20th compliance window}';

    protected $description = 'Remind employees to complete weekly time sheets for the 20th-to-20th compliance window (HR email + in-app notification)';

    public function handle(
        WeeklyTimeLogService $timeLogs,
        PlatformNotificationService $notifications,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $today = now()->startOfDay();

        if (! $force && ! $timeLogs->isPortalComplianceEnforcementWindow($today)) {
            $this->info('Outside the monthly compliance window (20th of previous month through 20th of current month). Use --force to override.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('staff_weekly_time_logs')) {
            $this->warn('Weekly time sheet tables are not installed.');

            return self::SUCCESS;
        }

        [, $windowEnd] = $timeLogs->portalComplianceWindow($today);
        $year = (int) $windowEnd->year;
        $month = (int) $windowEnd->month;
        $monthLabel = $windowEnd->copy()->startOfMonth()->format('F Y');
        $daysUntilDeadline = (int) $today->diffInDays($windowEnd, false);
        $entityId = sprintf('%04d-%02d', $year, $month);
        $notificationTitle = 'Weekly time sheets due — '.$monthLabel;
        $portalUrl = route('employee.time-logs.index');

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($timeLogs->staffEligibleForTimeLogReminders() as $staff) {
            $incomplete = $timeLogs->incompleteWeeksForPortalCompliance($staff, $today);
            if ($incomplete === []) {
                $skipped++;

                continue;
            }

            $userId = (int) $staff->user_id;
            if ($timeLogs->reminderSentForMonth($userId, $entityId, $notificationTitle)) {
                $skipped++;

                continue;
            }

            $email = $staff->resolveErpEmail($staff->user?->email);
            if ($email === null || $email === '') {
                $this->warn("Skipping {$staff->fullName()} — no email on record.");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $weeks = implode(', ', array_column($incomplete, 'week_ref'));
                $this->line("Would remind → {$staff->fullName()} <{$email}> ({$weeks})");
                $sent++;

                continue;
            }

            $mailResult = ModuleMail::trySend(
                ModuleMail::HR,
                $email,
                new WeeklyTimeLogReminderMail($staff, $monthLabel, $daysUntilDeadline, $incomplete, $portalUrl),
            );

            if (! $mailResult['sent']) {
                $failed++;
                $this->warn("Email failed → {$staff->fullName()}: ".($mailResult['error'] ?? 'unknown error'));

                continue;
            }

            $weekList = implode(', ', array_column($incomplete, 'week_ref'));
            $body = 'Human Resources reminds you to complete and submit your weekly time sheets for '.$monthLabel
                .' through the week of the 20th (due by '.$windowEnd->format('j M Y').'). Still open: '.$weekList.'.';

            $notifications->notifyUser(
                $userId,
                $notificationTitle,
                $body,
                'weekly_time_log_reminder',
                $entityId,
                'high',
                $portalUrl,
                false,
            );

            $sent++;
            $this->line("Reminded → {$staff->fullName()}");
        }

        $this->info(($dryRun ? 'Dry run: ' : '')."{$sent} reminded, {$skipped} skipped, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

}
