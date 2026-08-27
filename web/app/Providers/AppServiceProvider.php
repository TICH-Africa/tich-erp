<?php

namespace App\Providers;

use App\Services\RBACService;
use App\View\Composers\AcademicsSidebarComposer;
use App\View\Composers\AdminSidebarComposer;
use App\View\Composers\AdministrationSidebarComposer;
use App\View\Composers\EmailBrandComposer;
use App\View\Composers\EmployeeSidebarComposer;
use App\View\Composers\FinanceSidebarComposer;
use App\View\Composers\HrSidebarComposer;
use App\View\Composers\PublicLayoutComposer;
use App\View\Composers\StaffSidebarComposer;
use App\View\Composers\StudentSidebarComposer;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
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
        $this->configurePublicUrls();

        // Prefer HttpOnly + SameSite session cookies (secure when HTTPS / FORCE_HTTPS).
        if (config('security.force_https') && config('session.secure') === null) {
            config(['session.secure' => true]);
        }

        Gate::before(function ($user, string $ability) {
            if (! $user) {
                return null;
            }

            return app(RBACService::class)->hasPermission($user, $ability) ? true : null;
        });

        Route::bind('budgetRequest', function ($value) {
            return \App\Models\Administration\BudgetRequest::query()->findOrFail($value);
        });

        Route::bind('allocation', function ($value) {
            return \App\Models\Administration\FundAllocation::query()->findOrFail($value);
        });

        View::composer(['layouts.app', 'layouts.auth'], PublicLayoutComposer::class);
        View::composer(['emails.*', 'emails.finance.*'], EmailBrandComposer::class);
        View::composer(['hr.partials.sidebar', 'layouts.hr'], HrSidebarComposer::class);
        View::composer(['finance.partials.sidebar', 'layouts.finance'], FinanceSidebarComposer::class);
        View::composer(['employee.partials.sidebar', 'layouts.employee'], EmployeeSidebarComposer::class);
        View::composer(['staff.partials.sidebar', 'layouts.staff'], StaffSidebarComposer::class);
        View::composer(['portal.partials.sidebar', 'layouts.portal'], StudentSidebarComposer::class);
        View::composer(['academics.partials.sidebar', 'layouts.academics'], AcademicsSidebarComposer::class);
        View::composer(['admin.partials.sidebar', 'layouts.admin'], AdminSidebarComposer::class);
        View::composer(['administration.partials.sidebar', 'layouts.administration'], AdministrationSidebarComposer::class);

        // Keep deploy/production.sql in sync whenever migrations finish successfully (local/dev).
        Event::listen(MigrationsEnded::class, function (): void {
            if (app()->environment('production')) {
                return;
            }

            try {
                Artisan::call('tich:export-production-schema');
            } catch (\Throwable) {
                // Never fail a migration run because the SQL export could not write.
            }
        });
    }

    /**
     * Make route()/asset()/Storage URLs resolve from APP_URL (and ASSET_URL) in production.
     */
    private function configurePublicUrls(): void
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '' && filter_var(config('app.force_root_url'), FILTER_VALIDATE_BOOLEAN)) {
            URL::forceRootUrl($appUrl);
        }

        $forceHttps = (bool) config('security.force_https', false)
            || filter_var(config('app.force_https_urls'), FILTER_VALIDATE_BOOLEAN)
            || str_starts_with($appUrl, 'https://');

        if ($forceHttps) {
            URL::forceScheme('https');
        }

        // Keep the public disk aligned with ASSET_URL / APP_URL at runtime.
        $assetRoot = rtrim((string) (config('app.asset_url') ?: $appUrl), '/');
        if ($assetRoot !== '') {
            config(['filesystems.disks.public.url' => $assetRoot.'/storage']);
        }
    }
}
