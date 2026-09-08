<?php

namespace App\Providers;

use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QaDepartmentSubmission;
use App\Models\Qa\QaPlan;
use App\Services\Sidebar\CeoSidebarNotificationService;
use App\Services\Sidebar\QaSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class QaSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(QaSidebarNotificationService::class)->broadcastCounts();
            try {
                app(CeoSidebarNotificationService::class)->broadcastCounts();
            } catch (\Throwable) {
            }
        };

        foreach ([
            QaPlan::class,
            QaCorrectiveAction::class,
            QaDepartmentSubmission::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
