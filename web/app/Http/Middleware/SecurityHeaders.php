<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds browser security headers and strips server fingerprint headers.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $headers = config('security.headers', []);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', (string) ($headers['frame_options'] ?? 'SAMEORIGIN'));
        $response->headers->set('Referrer-Policy', (string) ($headers['referrer_policy'] ?? 'strict-origin-when-cross-origin'));
        $response->headers->set('Permissions-Policy', (string) ($headers['permissions_policy'] ?? ''));
        $response->headers->set('X-XSS-Protection', '0'); // Modern browsers use CSP; legacy header can cause issues.

        // Never let HTML-oriented CSP rewrite Content-Type for binary/static payloads.
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        $isStaticAsset = (bool) preg_match(
            '#^(text/css|text/javascript|application/javascript|application/json|image/|font/|application/(font|wasm))#',
            $contentType
        );

        $csp = trim((string) ($headers['content_security_policy'] ?? ''));
        if ($csp !== '' && ! $isStaticAsset) {
            // Avoid upgrade-insecure-requests on plain local HTTP so assets keep loading.
            if (! $request->isSecure() && ! config('security.force_https', false)) {
                $csp = trim(str_replace('upgrade-insecure-requests', '', $csp));
                $csp = preg_replace('/;\s*;/', ';', $csp) ?? $csp;
            }
            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($request->isSecure() || config('security.force_https', false)) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Hide framework / server fingerprinting where PHP can control it.
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
