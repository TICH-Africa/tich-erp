<?php

use App\Services\AuthService;
use App\Services\ErrorNavigationService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
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
        $middleware->web(append: [
            \App\Http\Middleware\CaptureClientContext::class,
        ]);

        $middleware->alias([
            'mfa' => \App\Http\Middleware\RequireMFA::class,
            'mfa.setup' => \App\Http\Middleware\EnsureMfaConfigured::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'student.portal' => \App\Http\Middleware\EnsureStudentPortalAccess::class,
            'staff.portal' => \App\Http\Middleware\EnsureStaffPortalAccess::class,
            'employee.portal' => \App\Http\Middleware\EnsureEmployeePortalAccess::class,
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
    })->create();
