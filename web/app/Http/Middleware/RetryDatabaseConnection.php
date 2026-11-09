<?php

namespace App\Http\Middleware;

use App\Support\DatabaseAvailability;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared hosts often drop idle MySQL connections; retry briefly before surfacing 503.
 */
class RetryDatabaseConnection
{
    private const MAX_ATTEMPTS = 3;

    public function handle(Request $request, Closure $next): Response
    {
        $this->warmConnection();

        return $next($request);
    }

    private function warmConnection(): void
    {
        $last = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                DB::connection()->getPdo();

                return;
            } catch (\Throwable $e) {
                $last = $e;
                if (! DatabaseAvailability::isUnavailable($e) || $attempt === self::MAX_ATTEMPTS) {
                    throw $e;
                }

                DB::purge(DB::getDefaultConnection());
                usleep(150_000 * $attempt);
            }
        }

        if ($last instanceof \Throwable) {
            throw $last;
        }
    }
}
