<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('apply/*')) {
                return redirect()
                    ->route('apply.index', ['step' => 5])
                    ->withErrors([
                        'documents' => 'One or more files exceed the server upload limit. Passport photos must be JPEG, PNG, or WebP images under 2 MB. Other documents must be under 5 MB.',
                    ]);
            }

            return null;
        });
    })->create();
