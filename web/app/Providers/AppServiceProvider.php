<?php

namespace App\Providers;

use App\Services\RBACService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if (! $user) {
                return null;
            }

            return app(RBACService::class)->hasPermission($user, $ability) ? true : null;
        });
    }
}
