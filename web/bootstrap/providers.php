<?php

use App\Providers\AppServiceProvider;
use App\Providers\TichSecurityServiceProvider;

return [
    AppServiceProvider::class,
    TichSecurityServiceProvider::class,
    App\Providers\HrSidebarNotificationServiceProvider::class,
    App\Providers\PortalSidebarNotificationServiceProvider::class,
];
