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
    App\Providers\MeSidebarNotificationServiceProvider::class,
    App\Providers\QaSidebarNotificationServiceProvider::class,
    App\Providers\CeoSidebarNotificationServiceProvider::class,
    App\Providers\IctSidebarNotificationServiceProvider::class,
];
