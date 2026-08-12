<?php

namespace App\Providers;

use App\Services\RBACService;
use App\View\Composers\AcademicsSidebarComposer;
use App\View\Composers\EmployeeSidebarComposer;
use App\View\Composers\FinanceSidebarComposer;
use App\View\Composers\HrSidebarComposer;
use App\View\Composers\PublicLayoutComposer;
use App\View\Composers\StaffSidebarComposer;
use App\View\Composers\StudentSidebarComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Support\ImageWebpEncoder::class);
        $this->app->singleton(\App\Services\StoredFileService::class);
    }

    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if (! $user) {
                return null;
            }

            return app(RBACService::class)->hasPermission($user, $ability) ? true : null;
        });

        View::composer(['layouts.app', 'layouts.auth'], PublicLayoutComposer::class);
        View::composer(['hr.partials.sidebar', 'layouts.hr'], HrSidebarComposer::class);
        View::composer(['finance.partials.sidebar', 'layouts.finance'], FinanceSidebarComposer::class);
        View::composer(['employee.partials.sidebar', 'layouts.employee'], EmployeeSidebarComposer::class);
        View::composer(['staff.partials.sidebar', 'layouts.staff'], StaffSidebarComposer::class);
        View::composer(['portal.partials.sidebar', 'layouts.portal'], StudentSidebarComposer::class);
        View::composer(['academics.partials.sidebar', 'layouts.academics'], AcademicsSidebarComposer::class);
    }
}
