<?php

namespace App\Console\Commands;

use App\Models\MpesaStkRequest;
use App\Services\Finance\MpesaSettingsService;
use App\Services\Finance\MpesaStkCallbackService;
use Illuminate\Console\Command;

class ReconcilePendingMpesaStkRequests extends Command
{
    protected $signature = 'finance:mpesa-reconcile-pending';

    protected $description = 'Query Safaricom for pending M-Pesa STK push payments and settle confirmed ones';

    public function handle(MpesaSettingsService $settings, MpesaStkCallbackService $callbackService): int
    {
        if (! $settings->isEnabled()) {
            $this->warn('M-Pesa is not enabled.');

            return self::SUCCESS;
        }

        $pending = MpesaStkRequest::query()
            ->where('status', MpesaStkRequest::STATUS_PENDING)
            ->whereNotNull('checkout_request_id')
            ->where('created_at', '<=', now()->subSeconds(45))
            ->where('created_at', '>=', now()->subMinutes(30))
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending STK requests to reconcile.');

            return self::SUCCESS;
        }

        foreach ($pending as $stkRequest) {
            $updated = $callbackService->reconcilePending($stkRequest);
            $this->line("#{$updated->id} {$updated->checkout_request_id} → {$updated->status}");
        }

        return self::SUCCESS;
    }
}
