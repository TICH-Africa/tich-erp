<?php

/**
 * Upload to public_html/tich-diagnose.php (File Manager) or copy on deploy.
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>TICH diagnose</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:52rem;margin:2rem auto;padding:0 1rem;line-height:1.45}';
echo '.ok{color:#166534}.bad{color:#b91c1c}pre{background:#f5f6f6;padding:.75rem;overflow:auto;white-space:pre-wrap}</style></head><body>';
echo '<h1>TICH diagnose</h1><ul>';

function row($label, $ok, $detail = '')
{
    $class = $ok ? 'ok' : 'bad';
    $mark = $ok ? 'OK' : 'FAIL';
    echo '<li><strong class="'.$class.'">['.$mark.']</strong> '.htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    if ($detail !== '') {
        echo ' - <code>'.htmlspecialchars($detail, ENT_QUOTES, 'UTF-8').'</code>';
    }
    echo '</li>';
}

row('PHP version (>= 8.2)', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION);

$appPath = '/home3/tichafri/tich-erp/web';
$candidates = array(
    $appPath,
    dirname(__DIR__).'/tich-erp/web',
);
foreach ($candidates as $candidate) {
    $candidate = rtrim(str_replace('\\', '/', $candidate), '/');
    if (is_file($candidate.'/artisan')) {
        $appPath = $candidate;
        break;
    }
}

row('Laravel path', is_file($appPath.'/artisan'), $appPath);
row('.env', is_file($appPath.'/.env'));
row('vendor/autoload.php', is_file($appPath.'/vendor/autoload.php'));
row('storage writable', is_writable($appPath.'/storage'));
row('bootstrap/cache writable', is_writable($appPath.'/bootstrap/cache'));

$envDebug = '(no .env)';
$envKey = false;
if (is_file($appPath.'/.env')) {
    $env = file_get_contents($appPath.'/.env');
    $envKey = (bool) preg_match('/^APP_KEY=base64:.+/m', $env);
    if (preg_match('/^APP_DEBUG=(.*)$/m', $env, $m)) {
        $envDebug = trim($m[1]);
    }
}
row('APP_KEY', $envKey, $envKey ? 'set' : 'MISSING');
row('APP_DEBUG in .env', true, $envDebug);

$configCache = $appPath.'/bootstrap/cache/config.php';
row('config cache absent', ! is_file($configCache), is_file($configCache) ? 'EXISTS - delete it (ignores .env APP_DEBUG)' : 'ok');

$logFile = $appPath.'/storage/logs/laravel.log';
row('laravel.log', is_file($logFile), $logFile);

echo '</ul><h2>Boot test</h2>';

try {
    if (! is_file($appPath.'/vendor/autoload.php')) {
        throw new RuntimeException('vendor/autoload.php missing');
    }
    require $appPath.'/vendor/autoload.php';
    $app = require $appPath.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    row('GET / status', $status < 500, (string) $status);
    if ($status >= 500) {
        echo '<pre>'.htmlspecialchars(substr(strip_tags($response->getContent()), 0, 2000), ENT_QUOTES, 'UTF-8').'</pre>';
    }
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    echo '<p class="bad"><strong>Boot exception: '.htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8').'</strong></p>';
    echo '<pre>'.htmlspecialchars($e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n\n".$e->getTraceAsString(), ENT_QUOTES, 'UTF-8').'</pre>';
}

if (is_file($logFile) && is_readable($logFile)) {
    $lines = @file($logFile);
    if (is_array($lines) && count($lines) > 0) {
        echo '<h2>laravel.log (tail)</h2><pre>'.htmlspecialchars(implode('', array_slice($lines, -60)), ENT_QUOTES, 'UTF-8').'</pre>';
    }
}

$deployLog = dirname($appPath).'/deploy/cpanel/last-deploy.log';
if (is_file($deployLog) && is_readable($deployLog)) {
    $size = filesize($deployLog);
    $max = 80000;
    $fh = fopen($deployLog, 'rb');
    if ($fh !== false) {
        if ($size > $max) {
            fseek($fh, -$max, SEEK_END);
            fread($fh, 256);
        }
        $tail = stream_get_contents($fh) ?: '';
        fclose($fh);
        echo '<h2>last-deploy.log (tail)</h2><pre>'.htmlspecialchars($tail, ENT_QUOTES, 'UTF-8').'</pre>';
    }
}

$autoloadFiles = $appPath.'/vendor/composer/autoload_files.php';
if (is_file($autoloadFiles) && str_contains((string) file_get_contents($autoloadFiles), 'mockery/mockery')) {
    echo '<p class="bad"><strong>Broken autoload:</strong> references mockery. Open <a href="/tich-fix-autoload.php">/tich-fix-autoload.php</a> once to repair.</p>';
}

echo '</body></html>';
