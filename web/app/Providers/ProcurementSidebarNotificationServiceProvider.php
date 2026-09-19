<?php

namespace App\Providers;

use App\Models\AssetAudit;
use App\Models\AssetDisposal;
use App\Models\GoodsReceivedNote;
use App\Models\ProcurementRequisition;
use App\Models\Rfq;
use App\Models\StockAlert;
use App\Models\StockIssue;
use App\Models\Supplier;
use App\Services\Procurement\ProcurementSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class ProcurementSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(ProcurementSidebarNotificationService::class)->broadcastCounts();
        };

        foreach ([
            ProcurementRequisition::class,
            Supplier::class,
            Rfq::class,
            GoodsReceivedNote::class,
            StockAlert::class,
            AssetDisposal::class,
            AssetAudit::class,
            StockIssue::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
