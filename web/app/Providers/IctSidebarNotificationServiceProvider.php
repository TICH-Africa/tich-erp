<?php

namespace App\Providers;

use App\Models\ErpRegistrationInvitation;
use App\Models\Portal\CmsPage;
use App\Services\Sidebar\IctSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class IctSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(IctSidebarNotificationService::class)->broadcastCounts();
        };

        foreach ([
            ErpRegistrationInvitation::class,
            CmsPage::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
