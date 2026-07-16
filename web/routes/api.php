<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\MFAController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/mfa/challenge', [AuthController::class, 'mfaChallenge']);
        Route::post('/mfa/setup', [AuthController::class, 'mfaSetup']);
        Route::post('/mfa/setup/verify', [AuthController::class, 'mfaSetupVerify']);
        Route::post('/mfa/disable', [AuthController::class, 'mfaDisable']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('mfa')->group(function () {
            Route::get('/status', [MFAController::class, 'getMFAStatus']);
            Route::post('/email/send', [MFAController::class, 'sendEmailOTP']);
            Route::post('/email/verify', [MFAController::class, 'verifyEmailOTP']);
            Route::post('/totp/setup', [MFAController::class, 'setupTOTP']);
            Route::post('/totp/enable', [MFAController::class, 'enableTOTP']);
            Route::post('/totp/verify', [MFAController::class, 'verifyTOTP']);
            Route::post('/backup/verify', [MFAController::class, 'verifyBackupCode']);
            Route::post('/disable', [MFAController::class, 'disableMFA']);
            Route::post('/backup/regenerate', [MFAController::class, 'regenerateBackupCodes']);
        });
    });
});

Route::middleware(['auth:sanctum', 'mfa', 'permission:audit_logs.read'])->prefix('admin')->group(function () {
    Route::get('/audit-logs', [AuditController::class, 'index']);
    Route::get('/audit-logs/{id}', [AuditController::class, 'show']);
    Route::get('/audit-logs/verify', [AuditController::class, 'verifyChain']);
    Route::get('/audit-logs/export', [AuditController::class, 'export']);
});
