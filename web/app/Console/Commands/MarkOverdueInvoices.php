<?php

namespace App\Console\Commands;

use App\Services\Finance\AccountsReceivableService;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'finance:mark-overdue-invoices';

    protected $description = 'Mark open student invoices as overdue when past due date';

    public function handle(AccountsReceivableService $ar): int
    {
        $count = $ar->markOverdueInvoices();
        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
