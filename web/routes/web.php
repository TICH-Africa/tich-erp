<?php

use App\Http\Controllers\Week4\ApplicationController;
use App\Http\Controllers\Week4\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Week 4 Routes — Student Onboarding Infrastructure
| All routes prefixed /week4
|--------------------------------------------------------------------------
*/

// ─── Public Application Portal ───────────────────────────────────────────

Route::prefix('apply')->group(function () {
    Route::get('/', [ApplicationController::class, 'showPortal'])->name('week4.application.portal');
    Route::post('/step/{step}', [ApplicationController::class, 'handleStep'])->name('week4.application.step');
    Route::post('/submit/{applicantId}', [ApplicationController::class, 'submitApplication'])->name('week4.application.submit');
    Route::get('/check-status', [ApplicationController::class, 'checkStatus'])->name('week4.application.status');
});

// ─── Admin Dashboard ──────────────────────────────────────────────────────

Route::prefix('week4')->middleware(['auth', 'permission:admissions:write'])->group(function () {
    Route::get('/dashboard', [OnboardingController::class, 'showDashboard'])->name('week4.dashboard');
    Route::get('/applications', [OnboardingController::class, 'listApplications'])->name('week4.applications.list');
    Route::get('/applications/{id}', [OnboardingController::class, 'reviewApplication'])->name('week4.application.review');
    Route::post('/applications/{id}/shortlist', [OnboardingController::class, 'shortlistApplication'])->name('week4.application.shortlist');
});