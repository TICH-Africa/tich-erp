<?php

/**
 * Check whether department module assignment can work on production.
 *
 * Upload to: public_html/tich-check-dept-modules.php
 * Visit:     https://tich.africa/tich-check-dept-modules.php?key=tich-check-2026
 * DELETE after use.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

if (($_GET['key'] ?? '') !== 'tich-check-2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$appPath = '/home3/tichafri/tich-erp/web';

echo '<h1>Department modules check</h1><pre>';

try {
    require $appPath.'/vendor/autoload.php';
    $app = require $appPath.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $hasTable = Illuminate\Support\Facades\Schema::hasTable('department_modules');
    $catalog = app(App\Services\DepartmentModuleService::class)->validModuleKeys();
    $configPath = $appPath.'/config/tich-department-modules.php';

    echo 'department_modules table: '.($hasTable ? 'YES' : 'NO — THIS IS THE PROBLEM')."\n";
    echo 'config file exists: '.(is_file($configPath) ? 'YES' : 'NO')."\n";
    echo 'catalog module count: '.count($catalog)."\n";
    echo 'catalog keys: '.implode(', ', $catalog)."\n";

    if ($hasTable) {
        $count = Illuminate\Support\Facades\DB::table('department_modules')->count();
        echo "department_modules rows: {$count}\n";
    } else {
        echo "\nFix: import the department_modules section from deploy/production.sql in phpMyAdmin,\n";
        echo "or run: php artisan migrate --force\n";
    }
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}

echo '</pre>';
echo '<p style="color:#b91c1c"><strong>DELETE this file now.</strong></p>';
