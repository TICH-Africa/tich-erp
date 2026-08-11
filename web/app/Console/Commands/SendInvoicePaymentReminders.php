<?php

namespace App\Console\Commands;

use App\Services\Finance\AccountsReceivableService;
use Illuminate\Console\Command;

class SendInvoicePaymentReminders extends Command
{
    protected $signature = 'finance:send-invoice-reminders {--dry-run : List reminders without sending}';

    protected $description = 'Send email/SMS payment reminders for due and overdue student invoices';

    public function handle(AccountsReceivableService $ar): int
    {
        $result = $ar->sendDueReminders((bool) $this->option('dry-run'));

        $this->info(sprintf(
            'Reminders: %d sent, %d failed.',
            $result['sent'],
            $result['failed'],
        ));

        return self::SUCCESS;
    }
}
