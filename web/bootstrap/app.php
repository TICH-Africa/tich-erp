<?php

use App\Services\AuthService;
use App\Services\ErrorNavigationService;
use App\Support\DatabaseAvailability;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Needed so ForceHttps / secure cookies see the real scheme behind Apache/XAMPP proxies.
        $middleware->trustProxies(at: '*');

        $middleware->web(prepend: [
            \App\Http\Middleware\ForceHttps::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ForceHttps::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\CaptureClientContext::class,
            \App\Http\Middleware\PreventDuplicateFormSubmission::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\PreventDuplicateFormSubmission::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'mfa' => \App\Http\Middleware\RequireMFA::class,
            'mfa.setup' => \App\Http\Middleware\EnsureMfaConfigured::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'student.portal' => \App\Http\Middleware\EnsureStudentPortalAccess::class,
            'staff.portal' => \App\Http\Middleware\EnsureStaffPortalAccess::class,
            'employee.portal' => \App\Http\Middleware\EnsureEmployeePortalAccess::class,
            'employee.profile.complete' => \App\Http\Middleware\EnsureEmployeeProfileComplete::class,
            'employee.unassigned.restrict' => \App\Http\Middleware\RestrictUnassignedEmployeeAccess::class,
            'resolve.academics.hub' => \App\Http\Middleware\ResolveAcademicsHub::class,
            'redirect.legacy.academics' => \App\Http\Middleware\RedirectLegacyAcademicsUrls::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));

        $middleware->validateCsrfTokens(except: [
            'webhooks/mpesa/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ], 419);
            }

            $message = 'Your session expired. Please try again.';
            $safeInput = $request->except('_token', 'password', 'password_confirmation', 'password_hash');

            if ($request->user()) {
                $home = app(ErrorNavigationService::class)->homeUrl($request->user());
                $referer = $request->headers->get('referer');
                $target = ($referer && $referer !== $request->fullUrl()) ? $referer : $home;

                return redirect()
                    ->to($target)
                    ->withInput($safeInput)
                    ->withErrors(['session' => $message]);
            }

            $referer = $request->headers->get('referer');
            if ($referer) {
                app(AuthService::class)->storeIntendedUrl($request, $referer);
            }

            return redirect()
                ->route('login')
                ->withInput($safeInput)
                ->withErrors(['session' => $message]);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'The requested resource was not found.'], 404);
            }

            return response()->view('errors.404', ['exception' => $e], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Request could not be completed.',
                ], $e->getStatusCode());
            }

            $status = $e->getStatusCode();
            $view = view()->exists("errors.{$status}") ? "errors.{$status}" : 'errors.minimal';

            return response()->view($view, ['exception' => $e], $status);
        });

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('apply/*')) {
                return redirect()
                    ->route('apply.index', ['step' => 5])
                    ->withErrors([
                        'documents' => 'One or more files exceed the server upload limit. Passport photos must be JPEG, PNG, or WebP images under 2 MB. Other documents must be under 5 MB.',
                    ]);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'The uploaded file is too large.'], 413);
            }

            $home = $request->user()
                ? app(ErrorNavigationService::class)->homeUrl($request->user())
                : route('home');

            return redirect()
                ->to($request->headers->get('referer') ?: $home)
                ->withErrors(['upload' => 'The uploaded file exceeds the server size limit. Try a smaller file.']);
        });

        // Never expose SQL / connection internals to end users (especially production).
        // Use a DB-free view so rendering the error cannot re-trigger the same failure.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! DatabaseAvailability::isUnavailable($e)) {
                return null;
            }

            try {
                Log::error('Database unavailable', [
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                    'url' => $request->fullUrl(),
                ]);
            } catch (\Throwable) {
                // Logging itself may need the DB - ignore.
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The service is temporarily unavailable. Please try again shortly.',
                ], 503);
            }

            return response()->view('errors.unavailable', [
                'code' => '503',
                'title' => 'Service unavailable',
                'message' => 'The platform is temporarily unavailable while we reconnect to the database.',
                'hint' => 'Please try again in a few minutes. If you manage this server, check that MariaDB/MySQL is running and allows local connections.',
            ], 503);
        });
    })->create();
