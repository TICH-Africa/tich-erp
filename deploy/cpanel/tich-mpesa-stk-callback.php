<?php

/**
 * M-Pesa STK callback bridge for cPanel / public_html.
 *
 * Safaricom Daraja requires a public HTTPS callback URL to send STK prompts.
 * Use this file when localhost/ngrok is not practical.
 *
 * Deploy target: public_html/tich-mpesa-stk-callback.php
 * Callback URL:  https://tich.africa/tich-mpesa-stk-callback.php
 *
 * Set in web/.env:
 *   MPESA_CALLBACK_URL=https://tich.africa/tich-mpesa-stk-callback.php
 *
 * Local dev: STK records live in your local DB. This production endpoint
 * processes callbacks against the production database. If you test from
 * localhost, payment confirmation still works via STK status polling on the
 * pay page (Safaricom stkQuery). For instant callback settlement on local,
 * use the same database as production or test on https://tich.africa.
 */

declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Method Not Allowed',
    ]);
    exit;
}

$appPath = '/home3/tichafri/tich-erp/web';

if (! is_file($appPath.'/vendor/autoload.php')) {
    http_response_code(503);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Application not ready',
    ]);
    exit;
}

require $appPath.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require $appPath.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$raw = file_get_contents('php://input');
$payload = json_decode($raw !== false && $raw !== '' ? $raw : '{}', true);

if (! is_array($payload)) {
    $payload = [];
}

try {
  Illuminate\Support\Facades\Log::info('M-Pesa STK callback received (public_html bridge)', [
      'checkout_request_id' => data_get($payload, 'Body.stkCallback.CheckoutRequestID'),
      'result_code' => data_get($payload, 'Body.stkCallback.ResultCode'),
  ]);

  $app->make(App\Services\Finance\MpesaStkCallbackService::class)->handle($payload);
} catch (Throwable $e) {
  Illuminate\Support\Facades\Log::error('M-Pesa STK callback bridge failed', [
      'message' => $e->getMessage(),
  ]);
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'ResultCode' => 0,
    'ResultDesc' => 'Accepted',
]);
