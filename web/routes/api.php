<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MFAController;
use App\Http\Controllers\RBACController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// MFA Routes
Route::middleware('auth:sanctum')->prefix('mfa')->group(function () {
    Route::post('/send-email-otp', [MFAController::class, 'sendEmailOTP']);
    Route::post('/verify-email-otp', [MFAController::class, 'verifyEmailOTP']);
    Route::post('/setup-totp', [MFAController::class, 'setupTOTP']);
    Route::post('/enable-totp', [MFAController::class, 'enableTOTP']);
    Route::post('/enable-email-mfa', [MFAController::class, 'enableEmailMFA']);
    Route::post('/verify-totp', [MFAController::class, 'verifyTOTP']);
    Route::post('/verify-backup-code', [MFAController::class, 'verifyBackupCode']);
    Route::post('/disable-mfa', [MFAController::class, 'disableMFA']);
    Route::get('/status', [MFAController::class, 'getMFAStatus']);
    Route::post('/regenerate-backup-codes', [MFAController::class, 'regenerateBackupCodes']);
});

// RBAC Routes
Route::middleware('auth:sanctum')->prefix('rbac')->group(function () {
    Route::get('/user/permissions', [RBACController::class, 'getUserPermissions']);
    Route::get('/user/roles', [RBACController::class, 'getUserRoles']);
    Route::post('/assign-role', [RBACController::class, 'assignRole']);
    Route::post('/revoke-role', [RBACController::class, 'revokeRole']);
    Route::post('/assign-permission', [RBACController::class, 'assignPermission']);
    Route::post('/revoke-permission', [RBACController::class, 'revokePermission']);
    Route::get('/roles', [RBACController::class, 'getRoles']);
    Route::get('/permissions', [RBACController::class, 'getPermissions']);
    Route::get('/roles/{role_id}/permissions', [RBACController::class, 'getRolePermissions']);
    Route::post('/roles/{role_id}/permissions', [RBACController::class, 'assignPermissionsToRole']);
});
