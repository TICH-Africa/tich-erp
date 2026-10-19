<?php

namespace App\Console\Commands;

use App\Services\MobileReminderService;
use Illuminate\Console\Command;

class RemindMissingClockIns extends Command
{
    protected $signature = 'attendance:remind-clock-in';

    protected $description = 'Send 30-minute clock-in reminders (from 08:00 EAT) until staff clock in';

    public function handle(MobileReminderService $reminders): int
    {
        $result = $reminders->remindMissingClockIns();
        $this->info("Checked {$result['checked']} staff; reminded {$result['reminded']}.");

        return self::SUCCESS;
    }
}
