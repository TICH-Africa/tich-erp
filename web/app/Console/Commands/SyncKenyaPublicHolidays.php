<?php

namespace App\Console\Commands;

use App\Services\KenyaPublicHolidayService;
use Illuminate\Console\Command;

class SyncKenyaPublicHolidays extends Command
{
    protected $signature = 'holidays:sync-kenya {--from= : Start year (inclusive)} {--to= : End year (inclusive)}';

    protected $description = 'Sync Kenyan public holidays from Nager.Date + Aladhan into public_holidays';

    public function handle(KenyaPublicHolidayService $holidays): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if ($from !== null && $to !== null) {
            $count = $holidays->syncRange((int) $from, (int) $to);
            $this->info("Synced {$count} holiday row(s) for years {$from}–{$to}.");

            return self::SUCCESS;
        }

        $count = $holidays->syncConfiguredWindow();
        $this->info("Synced {$count} holiday row(s) for the configured year window.");

        return self::SUCCESS;
    }
}
