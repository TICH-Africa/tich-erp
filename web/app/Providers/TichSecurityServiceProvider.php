<?php

namespace App\Providers;

use App\Services\EncryptionService;
use App\Services\RBACService;
use App\Services\MFAService;
use Illuminate\Support\ServiceProvider;

class TichSecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptionService::class, function ($app) {
            return new EncryptionService();
        });

        $this->app->singleton(RBACService::class, function ($app) {
            return new RBACService();
        });

        $this->app->singleton(MFAService::class, function ($app) {
            return new MFAService();
        });
    }

    public function boot(): void
    {
        //
    }
}