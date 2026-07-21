<?php

namespace App\Http\Middleware;

use App\Support\ClientContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureClientContext
{
    public function __construct(protected ClientContextResolver $clientContextResolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->has('client_device_type')) {
            $request->session()->put(
                'audit.client_context',
                $this->clientContextResolver->fromRequest($request)
            );
        }

        return $next($request);
    }
}
