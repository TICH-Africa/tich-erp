<?php

namespace App\Providers;

use App\Services\Ict\PlatformPerformanceService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

class PlatformPerformanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlatformPerformanceService::class);
    }

    public function boot(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $event): void {
            try {
                app(PlatformPerformanceService::class)->recordQuery((float) $event->time, false);
            } catch (Throwable) {
                // ignore
            }
        });

        $this->app->terminating(function (): void {
            PlatformPerformanceService::flushResolved();
        });

        // Console commands and queue jobs never run the HTTP terminate stage.
        register_shutdown_function(static function (): void {
            PlatformPerformanceService::flushResolved();
        });
    }
}
