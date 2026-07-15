<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ─── Authentication & MFA ─────────────────────────────────────────────────

Route::prefix('auth')->group(function () {

    // Standard login
    Route::post('/login', [AuthController::class, 'login']);

    // MFA challenge — after login credentials verified
    Route::post('/mfa/challenge',  [AuthController::class, 'mfaChallenge'])
        ->middleware('auth:sanctum');

    // MFA setup initiation
    Route::post('/mfa/setup',      [AuthController::class, 'mfaSetup'])
        ->middleware('auth:sanctum');

    // MFA setup verification (confirm first TOTP scan)
    Route::post('/mfa/setup/verify', [AuthController::class, 'mfaSetupVerify'])
        ->middleware('auth:sanctum');

    // Disable MFA (requires current password + reason)
    Route::post('/mfa/disable',    [AuthController::class, 'mfaDisable'])
        ->middleware('auth:sanctum');

    Route::post('/logout',          [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
});

// ─── MFA Status for Logged-in User ───────────────────────────────────────

Route::middleware('auth:sanctum')->get('/mfa/status', function (\Illuminate\Http\Request $request) {
    $user = $request->user('sanctum');
    return response()->json([
        'mfa_enabled'   => (bool) $user->mfa_enabled,
        'mfa_method'    => $user->mfa_method,
        'mfa_verified'  => $request->session()->has('mfa_verified_at'),
    ]);
});

// ─── Audit Log Viewer ────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'permission:audit_logs:read'])->prefix('admin')->group(function () {

    Route::get('/audit-logs',        [AuditController::class, 'index']);
    Route::get('/audit-logs/{id}',   [AuditController::class, 'show']);
    Route::get('/audit-logs/verify', [AuditController::class, 'verifyChain']);

    // Export (itself audited)
    Route::get('/audit-logs/export', [AuditController::class, 'export'])
        ->middleware('permission:audit_logs:read');
});