<?php

namespace App\Http\Controllers\Api\Procurement\mobile\Concerns;

use App\Services\Procurement\ProcurementSidebarNotificationService;

trait BroadcastsProcurementCounts
{
    protected function broadcastProcurementCounts(): void
    {
        app(ProcurementSidebarNotificationService::class)->broadcastCounts();
    }
}
