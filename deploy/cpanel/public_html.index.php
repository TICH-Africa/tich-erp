<?php

/**
 * public_html/index.php - bridge to Laravel at tich-erp/web
 * Copied to /home3/tichafri/public_html/index.php on each Git deploy.
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// ?tich_boot=1 — prove this bridge is live even when Laravel is down
if (isset($_GET['tich_boot'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "tich bridge ok\n";
    echo 'php='.PHP_VERSION."\n";
    echo 'time='.gmdate('c')."\n";
    exit;
}

function tich_fail($title, $detail)
{
    if (! headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>TICH error</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:48rem;margin:2rem auto;padding:0 1rem;line-height:1.45}';
    echo 'h1{color:#b91c1c;font-size:1.25rem}pre{background:#f5f6f6;padding:1rem;white-space:pre-wrap;overflow:auto}</style></head><body>';
    echo '<h1>'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h1>';
    echo '<pre>'.htmlspecialchars($detail, ENT_QUOTES, 'UTF-8').'</pre>';
    echo '<p>Open <a href="/tich-diagnose.php">/tich-diagnose.php</a> or append <code>?tich_boot=1</code> to confirm this bridge file is active.</p>';
    echo '</body></html>';
    exit;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error === null) {
        return;
    }
    $fatals = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
    if (! in_array($error['type'], $fatals, true)) {
        return;
    }
    tich_fail(
        'PHP fatal error',
        $error['message']."\n".$error['file'].':'.$error['line']
    );
});

$appPath = '/home3/tichafri/tich-erp/web';
$home = isset($_SERVER['HOME']) ? rtrim(str_replace('\\', '/', $_SERVER['HOME']), '/') : '';
$candidates = array(
    $appPath,
    $home !== '' ? $home.'/tich-erp/web' : '',
    dirname(__DIR__).'/tich-erp/web',
);

foreach ($candidates as $candidate) {
    if ($candidate === '') {
        continue;
    }
    $candidate = rtrim(str_replace('\\', '/', $candidate), '/');
    if (is_file($candidate.'/artisan') && is_file($candidate.'/public/index.php')) {
        $appPath = $candidate;
        break;
    }
}

$laravelPublic = $appPath.'/public';
$storagePublic = $appPath.'/storage/app/public';

if (! is_file($laravelPublic.'/index.php')) {
    tich_fail('Laravel public/index.php missing', 'Looked for app at: '.$appPath);
}

$requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$requestPath = rawurldecode((string) (parse_url($requestUri, PHP_URL_PATH) ? parse_url($requestUri, PHP_URL_PATH) : '/'));

if (strcasecmp($requestPath, '/favicon.ico') === 0) {
    foreach (array(
        $laravelPublic.'/favicon.ico',
        $laravelPublic.'/images/favicon-48.png',
        $laravelPublic.'/images/favicon.png',
    ) as $faviconFile) {
        if (is_file($faviconFile) && filesize($faviconFile) > 0) {
            tich_send_file($faviconFile);
            exit;
        }
    }
}

if ($requestPath !== '/' && strpos($requestPath, '..') === false) {
    $relative = ltrim($requestPath, '/');
    $lower = strtolower($relative);
    if ($relative !== '' && substr($lower, -4) !== '.php') {
        $files = array($laravelPublic.'/'.$relative);
        if (strpos($relative, 'storage/') === 0) {
            $files[] = $storagePublic.'/'.substr($relative, strlen('storage/'));
        }
        foreach ($files as $assetFile) {
            if (is_file($assetFile)) {
                tich_send_file($assetFile);
                exit;
            }
        }
    }
}

if (! is_file($appPath.'/vendor/autoload.php')) {
    tich_fail(
        'Composer vendor missing',
        "Missing {$appPath}/vendor/autoload.php\nRe-run cPanel Git Deploy (composer install)."
    );
}

if (! is_file($appPath.'/.env') && ! is_file($appPath.'/bootstrap/cache/config.php')) {
    tich_fail('Missing web/.env', "Create {$appPath}/.env with APP_KEY and DB_* settings.");
}

$cachedConfig = $appPath.'/bootstrap/cache/config.php';
if (is_file($cachedConfig)) {
    $cached = @file_get_contents($cachedConfig);
    if (is_string($cached) && (strpos($cached, "'key' => ''") !== false || strpos($cached, '"key" => ""') !== false)) {
        @unlink($cachedConfig);
    }
}

try {
    require $laravelPublic.'/index.php';
} catch (Throwable $e) {
    tich_fail(
        'Laravel boot failed: '.get_class($e),
        $e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n\n".$e->getTraceAsString()
    );
}

function tich_send_file($absolutePath)
{
    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    $types = array(
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'mjs' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'txt' => 'text/plain; charset=UTF-8',
        'pdf' => 'application/pdf',
    );
    header('Content-Type: '.(isset($types[$ext]) ? $types[$ext] : 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: '.(string) filesize($absolutePath));
    if (in_array($ext, array('css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2'), true)) {
        header('Cache-Control: public, max-age=604800');
    }
    readfile($absolutePath);
}
