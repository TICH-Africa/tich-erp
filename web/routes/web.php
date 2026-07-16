<?php

use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Week4\ApplicationController;
use App\Http\Controllers\Week4\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

/*
|--------------------------------------------------------------------------
| Authentication (Web)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register']);
    Route::get('/forgot-password', [WebAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [WebAuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [WebAuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [WebAuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [WebAuthController::class, 'showMfaSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [WebAuthController::class, 'setupMfa']);
    Route::get('/mfa/verify', [WebAuthController::class, 'showMfaVerify'])->name('mfa.verify');
    Route::post('/mfa/verify', [WebAuthController::class, 'verifyMfa']);
    Route::post('/mfa/resend', [WebAuthController::class, 'resendMfaCode'])->name('mfa.resend');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Protected application routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'mfa.setup', 'mfa'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('permission:dashboard.access')->name('dashboard');

    Route::prefix('week4')->middleware(['permission:admissions.read'])->group(function () {
        Route::get('/dashboard', [OnboardingController::class, 'showDashboard'])
            ->middleware('permission:admissions.read')
            ->name('week4.dashboard');
        Route::get('/applications', [OnboardingController::class, 'listApplications'])
            ->middleware('permission:admissions.read')
            ->name('week4.applications.list');
        Route::get('/applications/{id}', [OnboardingController::class, 'reviewApplication'])
            ->middleware('permission:admissions.read')
            ->name('week4.application.review');
        Route::post('/applications/{id}/shortlist', [OnboardingController::class, 'shortlistApplication'])
            ->middleware('permission:admissions.write')
            ->name('week4.application.shortlist');
    });
});

/*
|--------------------------------------------------------------------------
| Public Application Portal
|--------------------------------------------------------------------------
*/

Route::prefix('apply')->group(function () {
    Route::get('/', [ApplicationController::class, 'showPortal'])->name('week4.application.portal');
    Route::post('/step/{step}', [ApplicationController::class, 'handleStep'])->name('week4.application.step');
    Route::post('/submit/{applicantId}', [ApplicationController::class, 'submitApplication'])->name('week4.application.submit');
    Route::get('/check-status', [ApplicationController::class, 'checkStatus'])->name('week4.application.status');
});
