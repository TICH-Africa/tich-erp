<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Blocks duplicate POSTs that reuse the same client _submit_nonce
 * (injected by tich-form-submit-once.js). Protects against double-clicks
 * and refresh/resubmit of the same form instance.
 */
class PreventDuplicateFormSubmission
{
    /** @var list<string> */
    private array $except = [
        'login',
        'logout',
        'webhooks/*',
        '*/sidebar-notifications',
        'broadcasting/auth',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $nonce = (string) ($request->input('_submit_nonce')
            ?: $request->header('Idempotency-Key', ''));
        if ($nonce === '' || strlen($nonce) > 128) {
            return $next($request);
        }

        $userPart = $request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'guest';
        $cacheKey = 'form_submit:'.$userPart.':'.hash('sha256', $nonce);

        if (! Cache::add($cacheKey, 1, now()->addMinutes(10))) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'This action was already submitted. Refresh the page if you need to create another record.',
                ], 409);
            }

            $fallback = url('/');
            try {
                $redirect = redirect()->back(fallback: $fallback);
            } catch (\Throwable) {
                $redirect = redirect()->to($fallback);
            }

            if ($request->session()->has('success') || $request->session()->has('status')) {
                return $redirect;
            }

            return $redirect->with(
                'status',
                'That action was already submitted. No duplicate record was created. Refresh if you need to add another.'
            );
        }

        try {
            $response = $next($request);
        } catch (ValidationException $e) {
            Cache::forget($cacheKey);
            throw $e;
        } catch (Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                if ($status === 422 || $status >= 500) {
                    Cache::forget($cacheKey);
                }
            } else {
                // Unexpected failure — allow retry with same form nonce.
                Cache::forget($cacheKey);
            }
            throw $e;
        }

        // Allow a retry with the same nonce after validation / server errors.
        if ($this->shouldReleaseNonce($request, $response)) {
            Cache::forget($cacheKey);
        }

        return $response;
    }

    private function shouldReleaseNonce(Request $request, Response $response): bool
    {
        $status = $response->getStatusCode();
        if ($status >= 500) {
            return true;
        }

        if ($status === 422) {
            return true;
        }

        if ($request->hasSession() && $request->session()->has('errors')) {
            return true;
        }

        return false;
    }

    private function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
