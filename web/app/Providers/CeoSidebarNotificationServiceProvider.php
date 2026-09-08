<?php

namespace App\Providers;

use App\Models\Administration\BudgetRequest;
use App\Models\CurriculumVersion;
use App\Services\Sidebar\CeoSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class CeoSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(CeoSidebarNotificationService::class)->broadcastCounts();
        };

        foreach ([
            BudgetRequest::class,
            CurriculumVersion::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
