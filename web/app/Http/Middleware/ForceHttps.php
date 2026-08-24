<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.force_https', false)) {
            return $next($request);
        }

        if ($request->isSecure()) {
            return $next($request);
        }

        // Respect reverse-proxy HTTPS indicators.
        $forwarded = strtolower((string) $request->header('X-Forwarded-Proto', ''));
        if ($forwarded === 'https') {
            return $next($request);
        }

        $url = 'https://'.$request->getHttpHost().$request->getRequestUri();

        return redirect()->to($url, 301);
    }
}
