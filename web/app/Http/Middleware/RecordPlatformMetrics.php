<?php

namespace App\Http\Middleware;

use App\Services\Ict\PlatformPerformanceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordPlatformMetrics
{
    public function __construct(
        protected PlatformPerformanceService $metrics,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $started = hrtime(true);
        /** @var Response $response */
        $response = $next($request);
        $durationMs = (int) round((hrtime(true) - $started) / 1e6);

        try {
            $this->metrics->recordRequest(
                $durationMs,
                $response->getStatusCode(),
                memory_get_peak_usage(true) / 1048576
            );
        } catch (\Throwable) {
            // Metrics must never break the request.
        }

        // Approximate TTFB for the client as server-side handling time.
        $response->headers->set('X-Response-Time', $durationMs.'ms');

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        if ($request->is('up')) {
            return true;
        }

        // Avoid feedback loops from the metrics poller itself.
        if ($request->routeIs('ict.platform-performance.metrics')) {
            return true;
        }

        $path = ltrim($request->path(), '/');
        if ($path === '' ) {
            return false;
        }

        return (bool) preg_match('/\.(css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf)$/i', $path);
    }
}
