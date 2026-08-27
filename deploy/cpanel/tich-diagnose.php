<?php

/**
 * Temporary production diagnostic. Copied to public_html/tich-diagnose.php on deploy.
 * Delete from public_html after the site is healthy.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>TICH diagnose</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:52rem;margin:2rem auto;padding:0 1rem;line-height:1.45}';
echo 'ok{color:#166534}bad{color:#b91c1c}pre{background:#f5f6f6;padding:.75rem;overflow:auto;white-space:pre-wrap}';
echo 'h1{font-size:1.25rem}li{margin:.35rem 0}</style></head><body>';
echo '<h1>TICH production diagnose</h1><ul>';

function row(string $label, bool $ok, string $detail = ''): void
{
    $class = $ok ? 'ok' : 'bad';
    $mark = $ok ? 'OK' : 'FAIL';
    echo '<li><strong class="'.$class.'">['.$mark.']</strong> '.htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    if ($detail !== '') {
        echo ' — <code>'.htmlspecialchars($detail, ENT_QUOTES, 'UTF-8').'</code>';
    }
    echo '</li>';
}

row('PHP version (>= 8.2)', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION);

$candidates = [
    dirname(__DIR__).'/tich-erp/web',
    '/home3/tichafri/tich-erp/web',
    '/home2/tichafri/tich-erp/web',
    '/home/tichafri/tich-erp/web',
];
$overrideFile = dirname(__DIR__).'/tich-erp/deploy/cpanel/app-path.txt';
if (is_file($overrideFile)) {
    $override = trim((string) file_get_contents($overrideFile));
    if ($override !== '') {
        array_unshift($candidates, $override);
    }
}

$appPath = null;
foreach ($candidates as $candidate) {
    $candidate = rtrim($candidate, '/');
    if (is_file($candidate.'/artisan') && is_file($candidate.'/vendor/autoload.php')) {
        $appPath = $candidate;
        break;
    }
}

row('Laravel path resolved', $appPath !== null, $appPath ?? 'not found');
if ($appPath === null) {
    echo '</ul><p>Cannot continue.</p></body></html>';
    exit;
}

row('.env exists', is_file($appPath.'/.env'), $appPath.'/.env');
row('vendor/autoload.php', is_file($appPath.'/vendor/autoload.php'));
row('storage writable', is_writable($appPath.'/storage'), $appPath.'/storage');
row('bootstrap/cache writable', is_writable($appPath.'/bootstrap/cache'), $appPath.'/bootstrap/cache');
row('public/css/tich-platform.css', is_file($appPath.'/public/css/tich-platform.css'));

$envKey = false;
$envUrl = false;
$envDebug = null;
if (is_file($appPath.'/.env')) {
    $env = file_get_contents($appPath.'/.env') ?: '';
    $envKey = (bool) preg_match('/^APP_KEY=base64:.+/m', $env);
    $envUrl = (bool) preg_match('/^APP_URL=https?:\\/\\/\\S+/m', $env);
    if (preg_match('/^APP_DEBUG=(.*)$/m', $env, $m)) {
        $envDebug = trim($m[1]);
    }
}
row('APP_KEY set in .env', $envKey, $envKey ? 'base64:…' : 'missing/empty — run php artisan key:generate');
row('APP_URL set in .env', $envUrl);
row('APP_DEBUG value', true, (string) $envDebug);

$cachedConfig = $appPath.'/bootstrap/cache/config.php';
row('config.php cache present', is_file($cachedConfig), is_file($cachedConfig) ? 'yes (can poison boot if stale)' : 'no');

$logFile = $appPath.'/storage/logs/laravel.log';
row('laravel.log exists', is_file($logFile), $logFile);

echo '</ul>';

echo '<h2>Boot test</h2>';
try {
    require $appPath.'/vendor/autoload.php';
    $app = require $appPath.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    row('Kernel handle / status', $status < 500, (string) $status);
    if ($status >= 500) {
        $content = substr(strip_tags($response->getContent() ?: ''), 0, 800);
        echo '<pre>'.htmlspecialchars($content, ENT_QUOTES, 'UTF-8').'</pre>';
    }
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    row('Boot exception', false, $e::class);
    echo '<pre>'.htmlspecialchars($e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString(), ENT_QUOTES, 'UTF-8').'</pre>';
}

if (is_file($logFile) && is_readable($logFile)) {
    $lines = @file($logFile);
    if (is_array($lines) && $lines !== []) {
        $tail = array_slice($lines, -40);
        echo '<h2>laravel.log (last lines)</h2><pre>'.htmlspecialchars(implode('', $tail), ENT_QUOTES, 'UTF-8').'</pre>';
    }
}

$deployLog = dirname($appPath).'/deploy/cpanel/last-deploy.log';
if (is_file($deployLog)) {
    echo '<h2>last-deploy.log</h2><pre>'.htmlspecialchars((string) file_get_contents($deployLog), ENT_QUOTES, 'UTF-8').'</pre>';
} else {
    echo '<p class="bad">No last-deploy.log at '.$deployLog.' — you may have opened app-path.txt instead.</p>';
}

echo '<p>When fixed, delete <code>public_html/tich-diagnose.php</code> and add <code>deploy/cpanel/HIDE_ERRORS</code>.</p>';
echo '</body></html>';
