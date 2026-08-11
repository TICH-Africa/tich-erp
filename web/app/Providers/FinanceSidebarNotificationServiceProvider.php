<?php

namespace App\Providers;

use App\Models\FeeStructure;
use App\Models\Finance\FinancialAdjustment;
use App\Models\Finance\InstallmentPlanItem;
use App\Models\Finance\PaymentMilestone;
use App\Models\Finance\Refund;
use App\Models\Invoice;
use App\Models\MpesaStkRequest;
use App\Models\PayrollRun;
use App\Services\Finance\FinanceSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class FinanceSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(FinanceSidebarNotificationService::class)->broadcastCounts();
        };

        foreach ([
            Invoice::class,
            Refund::class,
            FinancialAdjustment::class,
            PayrollRun::class,
            FeeStructure::class,
            InstallmentPlanItem::class,
            PaymentMilestone::class,
            MpesaStkRequest::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
