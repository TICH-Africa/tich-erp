<?php

namespace App\Providers;

use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\Department;
use App\Services\Sidebar\AdminSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class AdminSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(AdminSidebarNotificationService::class)->broadcastCounts();
        };

        foreach ([
            Campus::class,
            Department::class,
            AcademicProgram::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
