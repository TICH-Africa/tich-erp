<?php

use App\Providers\AppServiceProvider;
use App\Providers\TichSecurityServiceProvider;

return [
    AppServiceProvider::class,
    TichSecurityServiceProvider::class,
    App\Providers\HrSidebarNotificationServiceProvider::class,
    App\Providers\FinanceSidebarNotificationServiceProvider::class,
    App\Providers\PortalSidebarNotificationServiceProvider::class,
    App\Providers\AdminSidebarNotificationServiceProvider::class,
    App\Providers\AdministrationSidebarNotificationServiceProvider::class,
];
