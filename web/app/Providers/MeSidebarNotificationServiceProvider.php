<?php

namespace App\Providers;

use App\Models\Me\MePolicy;
use App\Models\Me\MePolicySignoff;
use App\Models\Me\MeQuarterlyReport;
use App\Models\Me\MeTechnicalPlan;
use App\Services\Sidebar\MeSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class MeSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(MeSidebarNotificationService::class)->broadcastCounts();
            try {
                app(\App\Services\Sidebar\CeoSidebarNotificationService::class)->broadcastCounts();
            } catch (\Throwable) {
            }
        };

        foreach ([
            MePolicy::class,
            MePolicySignoff::class,
            MeTechnicalPlan::class,
            MeQuarterlyReport::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
