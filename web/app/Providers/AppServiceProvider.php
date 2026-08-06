<?php

namespace App\Providers;

use App\Services\RBACService;
use App\View\Composers\PublicLayoutComposer;
use App\View\Composers\HrSidebarComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        View::composer('layouts.app', PublicLayoutComposer::class);
        View::composer(['hr.partials.sidebar', 'layouts.hr'], HrSidebarComposer::class);
    }
}
