<?php

namespace App\Console\Commands;

use App\Services\MobileReminderService;
use Illuminate\Console\Command;

class RemindMissingClockOuts extends Command
{
    protected $signature = 'attendance:remind-clock-out';

    protected $description = 'Send clock-out reminders at 17:00, 17:15, and 17:30 EAT to staff still clocked in';

    public function handle(MobileReminderService $reminders): int
    {
        $result = $reminders->remindMissingClockOuts();
        $this->info("Checked {$result['checked']} open attendances; reminded {$result['reminded']}.");

        return self::SUCCESS;
    }
}
