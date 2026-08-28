<?php

/**
 * Ensure thin RBAC role rows from code catalog (no Terminal).
 *
 * Permissions / categories / nav / role↔permission templates are NOT seeded -
 * they live in config and are resolved at runtime.
 *
 * Upload to: public_html/tich-seed-essential.php
 * Visit:     https://tich.africa/tich-seed-essential.php?key=tich-seed-2026
 * DELETE the file when done.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

if (($_GET['key'] ?? '') !== 'tich-seed-2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$appPath = '/home3/tichafri/tich-erp/web';

echo '<h1>TICH ensure RBAC roles</h1><pre>';

try {
    require $appPath.'/vendor/autoload.php';
    $app = require $appPath.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    date_default_timezone_set('Africa/Nairobi');

    Illuminate\Support\Facades\Artisan::call('tich:ensure-rbac-roles');
    echo htmlspecialchars(Illuminate\Support\Facades\Artisan::output());
    echo "\nOK - role rows ensured from code. Permissions/nav are config-owned.\n";
} catch (Throwable $e) {
    echo 'ERROR: '.htmlspecialchars($e->getMessage())."\n";
    echo htmlspecialchars($e->getFile().':'.$e->getLine())."\n";
}

echo '</pre>';
echo '<p style="color:#b91c1c"><strong>DELETE public_html/tich-seed-essential.php now.</strong></p>';
