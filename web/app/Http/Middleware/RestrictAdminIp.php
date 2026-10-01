<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict administrative route prefixes to an IP allowlist when enabled.
 */
class RestrictAdminIp
{
    public function __construct(protected AuditService $auditService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $config = config('security.admin_ip_allowlist', []);

        if (! ($config['enabled'] ?? false)) {
            return $next($request);
        }

        $prefixes = $config['prefixes'] ?? ['admin', 'ict'];
        $path = ltrim($request->path(), '/');
        $isAdminPath = false;

        foreach ($prefixes as $prefix) {
            $prefix = trim((string) $prefix, '/');
            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix.'/'))) {
                $isAdminPath = true;
                break;
            }
        }

        if (! $isAdminPath) {
            return $next($request);
        }

        $allowed = $config['ips'] ?? [];
        $clientIp = $request->ip();

        if ($clientIp && $allowed !== [] && IpUtils::checkIp($clientIp, $allowed)) {
            return $next($request);
        }

        $this->auditService->log(
            'access.denied',
            'routes',
            $request->path(),
            null,
            [
                'reason' => 'admin_ip_allowlist',
                'ip' => $clientIp,
                'method' => $request->method(),
            ],
            'Admin IP allowlist blocked request',
            'failure',
            $request->user()?->id,
            $request
        );

        abort(403, 'Administrative access is not allowed from this network.');
    }
}
