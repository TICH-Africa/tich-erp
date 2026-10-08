<?php

/**
 * public_html/index.php - bridge to Laravel at tich-erp/web
 * Copied to /home3/tichafri/public_html/index.php on each Git deploy.
 *
 * Serves static assets (css/js/images) and uploaded media (/storage/…)
 * even when public_html/storage or public/storage symlinks are missing.
 *
 * Shows a clear boot error page when Laravel cannot start (empty LiteSpeed
 * 500s hide APP_DEBUG output).
 */

declare(strict_types=1);

$showErrors = true;
$showErrorsFlag = dirname(__DIR__).'/tich-erp/deploy/cpanel/SHOW_ERRORS';
$hideErrorsFlag = dirname(__DIR__).'/tich-erp/deploy/cpanel/HIDE_ERRORS';
if (is_file($showErrorsFlag)) {
    $showErrors = true;
}
if (is_file($hideErrorsFlag)) {
    $showErrors = false;
}

if ($showErrors) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

register_shutdown_function(static function () use (&$showErrors): void {
    $error = error_get_last();
    if ($error === null || ! $showErrors) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (! in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    if (headers_sent() === false) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<h1>PHP fatal error</h1><pre>'
        .htmlspecialchars($error['message']."\n".$error['file'].':'.$error['line'], ENT_QUOTES, 'UTF-8')
        .'</pre><p>Also open <code>/tich-diagnose.php</code> and check <code>web/storage/logs/laravel.log</code>.</p>';
});

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
    $candidate = rtrim(str_replace('\\', '/', $candidate), '/');
    if (
        $candidate !== ''
        && is_file($candidate.'/artisan')
        && is_file($candidate.'/bootstrap/app.php')
        && is_file($candidate.'/public/index.php')
    ) {
        $appPath = $candidate;
        break;
    }
}

if ($appPath === null) {
    tich_bridge_fail(
        'Laravel application not found',
        'Checked: '.implode(', ', $candidates),
        $candidates
    );
}

$laravelPublic = $appPath.'/public';
$storagePublic = $appPath.'/storage/app/public';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = rawurldecode((string) (parse_url($requestUri, PHP_URL_PATH) ?: '/'));

// Google Search crawls /favicon.ico directly; serve a static file even when docroot copy is missing.
if (strcasecmp($requestPath, '/favicon.ico') === 0) {
    foreach ([
        $laravelPublic.'/favicon.ico',
        $laravelPublic.'/images/favicon-48.png',
        $laravelPublic.'/images/favicon.png',
    ] as $faviconFile) {
        if (! is_file($faviconFile) || filesize($faviconFile) <= 0) {
            continue;
        }

        tich_serve_static_file($faviconFile);
        exit;
    }
}

if ($requestPath !== '/' && ! str_contains($requestPath, '..')) {
    $relative = ltrim($requestPath, '/');

    if ($relative !== '' && ! str_ends_with(strtolower($relative), '.php')) {
        $assetCandidates = [];

        // Preferred: Laravel public path (includes public/storage symlink).
        $assetCandidates[] = $laravelPublic.'/'.$relative;

        // Fallback for uploads: map /storage/foo → storage/app/public/foo
        if (str_starts_with($relative, 'storage/')) {
            $assetCandidates[] = $storagePublic.'/'.substr($relative, strlen('storage/'));
        }

        foreach ($assetCandidates as $assetFile) {
            if (! is_file($assetFile)) {
                continue;
            }

            tich_serve_static_file($assetFile);
            exit;
        }
    }
}

if (! is_file($appPath.'/vendor/autoload.php')) {
    tich_bridge_fail(
        'Composer dependencies missing (vendor/)',
        'Re-deploy from cPanel Git, or run composer install in '.$appPath,
        [$appPath]
    );
}

if (! is_file($appPath.'/.env') && ! is_file($appPath.'/bootstrap/cache/config.php')) {
    tich_bridge_fail(
        'Missing web/.env',
        'Create '.$appPath.'/.env with APP_KEY, APP_URL=https://tich.africa, and DB_* then run key:generate',
        [$appPath]
    );
}

// Stale config cache with empty APP_KEY is a common silent 500.
$cachedConfig = $appPath.'/bootstrap/cache/config.php';
if (is_file($cachedConfig)) {
    $cached = @file_get_contents($cachedConfig);
    if (is_string($cached) && (str_contains($cached, "'key' => ''") || str_contains($cached, '"key" => ""'))) {
        @unlink($cachedConfig);
    }
}

try {
    require $laravelPublic.'/index.php';
} catch (Throwable $e) {
    tich_bridge_fail(
        'Laravel boot failed: '.$e::class,
        $e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n\n".$e->getTraceAsString(),
        [$appPath]
    );
}

/**
 * @param  list<string>  $tried
 */
function tich_bridge_fail(string $title, string $hint, array $tried = []): never
{
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    $triedHtml = htmlspecialchars(implode("\n", $tried), ENT_QUOTES, 'UTF-8');
    $titleHtml = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $hintHtml = htmlspecialchars($hint, ENT_QUOTES, 'UTF-8');

    echo <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>TICH deploy error</title>
<style>
body{font-family:system-ui,sans-serif;max-width:46rem;margin:2.5rem auto;padding:0 1rem;line-height:1.5;color:#1f2933}
h1{color:#c53030;font-size:1.25rem} pre{background:#f5f6f6;padding:1rem;overflow:auto;white-space:pre-wrap}
code{background:#f5f6f6;padding:.15rem .35rem;border-radius:4px}
</style></head><body>
<h1>{$titleHtml}</h1>
<pre>{$hintHtml}</pre>
<p>Open <a href="/tich-diagnose.php"><code>/tich-diagnose.php</code></a> for a full checklist. Also check <code>deploy/cpanel/last-deploy.log</code> and <code>web/storage/logs/laravel.log</code>.</p>
<pre>{$triedHtml}</pre>
</body></html>
HTML;
    exit;
}

function tich_serve_static_file(string $absolutePath): void
{
    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'mjs' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'map' => 'application/json; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'txt' => 'text/plain; charset=UTF-8',
        'xml' => 'application/xml; charset=UTF-8',
        'pdf' => 'application/pdf',
        'webmanifest' => 'application/manifest+json',
    ];

    header('Content-Type: '.($types[$ext] ?? 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: '.(string) filesize($absolutePath));
    if (in_array($ext, ['css', 'js', 'mjs', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'woff', 'woff2', 'ico'], true)) {
        header('Cache-Control: public, max-age=604800');
    }
    readfile($absolutePath);
}
