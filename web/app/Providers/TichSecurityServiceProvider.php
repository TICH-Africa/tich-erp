<?php

namespace App\Providers;

use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\EncryptionService;
use App\Services\MFAService;
use App\Services\RBACService;
use Illuminate\Support\ServiceProvider;

class TichSecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptionService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(MFAService::class);
        $this->app->singleton(RBACService::class);
        $this->app->singleton(AuthService::class);
    }

    public function boot(): void
    {
        //
    }
}
