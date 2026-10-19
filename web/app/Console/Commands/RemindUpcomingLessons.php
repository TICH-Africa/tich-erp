<?php

namespace App\Console\Commands;

use App\Services\MobileReminderService;
use Illuminate\Console\Command;

class RemindUpcomingLessons extends Command
{
    protected $signature = 'academics:remind-upcoming-lessons';

    protected $description = 'Notify students about lessons starting in the next ~30 minutes';

    public function handle(MobileReminderService $reminders): int
    {
        $result = $reminders->remindUpcomingLessons();
        $this->info("Matched {$result['checked']} sessions; sent {$result['reminded']} reminders.");

        return self::SUCCESS;
    }
}
