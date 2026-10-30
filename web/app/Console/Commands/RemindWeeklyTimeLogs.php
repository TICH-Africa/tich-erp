<?php

namespace App\Console\Commands;

use App\Mail\WeeklyTimeLogReminderMail;
use App\Services\PlatformNotificationService;
use App\Services\WeeklyTimeLogService;
use App\Support\ModuleMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemindWeeklyTimeLogs extends Command
{
    protected $signature = 'hr:remind-weekly-time-logs {--dry-run : List recipients without sending} {--force : Run even outside the last week of the month}';

    protected $description = 'Remind employees to complete weekly time logs during the last week of each month (HR email + in-app notification)';

    public function handle(
        WeeklyTimeLogService $timeLogs,
        PlatformNotificationService $notifications,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $today = now()->startOfDay();

        if (! $force && ! $timeLogs->isMonthEndReminderWindow($today)) {
            $this->info('Outside the month-end reminder window (last 7 days of the month). Use --force to override.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('staff_weekly_time_logs')) {
            $this->warn('Weekly time log tables are not installed.');

            return self::SUCCESS;
        }

        $year = (int) $today->year;
        $month = (int) $today->month;
        $monthLabel = $today->copy()->startOfMonth()->format('F Y');
        $daysUntilMonthEnd = (int) $today->diffInDays($today->copy()->endOfMonth()->startOfDay(), false);
        $entityId = sprintf('%04d-%02d', $year, $month);
        $notificationTitle = 'Weekly time logs due — '.$monthLabel;
        $portalUrl = route('employee.time-logs.index');

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($timeLogs->staffEligibleForTimeLogReminders() as $staff) {
            $incomplete = $timeLogs->incompleteWeeksForStaff($staff, $year, $month, $today);
            if ($incomplete === []) {
                $skipped++;

                continue;
            }

            $userId = (int) $staff->user_id;
            if ($this->alreadyNotified($userId, $entityId, $notificationTitle)) {
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
                new WeeklyTimeLogReminderMail($staff, $monthLabel, $daysUntilMonthEnd, $incomplete, $portalUrl),
            );

            if (! $mailResult['sent']) {
                $failed++;
                $this->warn("Email failed → {$staff->fullName()}: ".($mailResult['error'] ?? 'unknown error'));

                continue;
            }

            $weekList = implode(', ', array_column($incomplete, 'week_ref'));
            $body = 'Human Resources reminds you to complete and submit your weekly time logs for '.$monthLabel
                .' before month end. Still open: '.$weekList.'.';

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

    private function alreadyNotified(int $userId, string $entityId, string $title): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('related_entity_type', 'weekly_time_log_reminder')
            ->where('related_entity_id', $entityId)
            ->where('title', $title)
            ->exists();
    }
}
