<?php

namespace App\Providers;

use App\Models\Administration\BudgetRequest;
use App\Models\Administration\InspectionCheck;
use App\Models\Administration\StatutoryCertification;
use App\Models\Applicant;
use App\Services\Sidebar\AdministrationSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class AdministrationSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(AdministrationSidebarNotificationService::class)->broadcastCounts();
        };

        foreach ([
            BudgetRequest::class,
            Applicant::class,
            StatutoryCertification::class,
            InspectionCheck::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
