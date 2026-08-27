<?php

/**
 * tich.africa document-root bridge (HostPinnacle / cPanel).
 * Copied to public_html/index.php on each Git deploy.
 */

declare(strict_types=1);

$showErrors = true; // keep true until site is stable; set false later or delete deploy/cpanel/SHOW_ERRORS
$showErrorsFlag = dirname(__DIR__).'/tich-erp/deploy/cpanel/SHOW_ERRORS';
if (is_file($showErrorsFlag)) {
    $showErrors = true;
}
if (is_file(dirname(__DIR__).'/tich-erp/deploy/cpanel/HIDE_ERRORS')) {
    $showErrors = false;
}

if ($showErrors) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

register_shutdown_function(static function () use (&$showErrors): void {
    $error = error_get_last();
    if ($error === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (! in_array($error['type'], $fatalTypes, true)) {
        return;
    }
    if (! $showErrors) {
        return;
    }
    if (headers_sent() === false) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<h1>PHP fatal error</h1><pre>'
        .htmlspecialchars($error['message']."\n".$error['file'].':'.$error['line'], ENT_QUOTES, 'UTF-8')
        .'</pre><p>Also check <code>/tich-diagnose.php</code> and <code>web/storage/logs/laravel.log</code>.</p>';
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
    bootstrap_fail(
        'Laravel application not found',
        'Checked: '.implode(', ', $candidates),
        $candidates
    );
}

$laravelPublic = $appPath.'/public';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = rawurldecode((string) (parse_url($requestUri, PHP_URL_PATH) ?: '/'));

// Serve static assets without booting Laravel (critical on hosts without symlinks).
if ($requestPath !== '/' && ! str_contains($requestPath, '..')) {
    $relative = ltrim($requestPath, '/');
    // Never treat PHP entrypoints as static assets.
    if (! str_ends_with(strtolower($relative), '.php')) {
        $assetFile = $laravelPublic.'/'.$relative;
        if (is_file($assetFile) && is_readable($assetFile)) {
            serve_static_file($assetFile);
            exit;
        }
    }
}

if (! is_file($appPath.'/vendor/autoload.php')) {
    bootstrap_fail(
        'Composer dependencies missing (vendor/)',
        'Deploy again or run composer install in '.$appPath,
        [$appPath]
    );
}

if (! is_file($appPath.'/.env') && ! is_file($appPath.'/bootstrap/cache/config.php')) {
    bootstrap_fail(
        'Missing web/.env',
        'Create '.$appPath.'/.env with APP_KEY, APP_URL=https://tich.africa, and DB_* then run key:generate',
        [$appPath]
    );
}

// Clear poisoned config cache if APP_KEY looks empty in cached config (common 500 cause).
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
    bootstrap_fail(
        'Laravel boot failed: '.$e::class,
        $e->getMessage()."\n".$e->getFile().':'.$e->getLine(),
        [$appPath]
    );
}

/**
 * @param  list<string>  $tried
 */
function bootstrap_fail(string $title, string $hint, array $tried = []): never
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
<p>Open <a href="/tich-diagnose.php"><code>/tich-diagnose.php</code></a> for a full checklist. Log: <code>deploy/cpanel/last-deploy.log</code></p>
<pre>{$triedHtml}</pre>
</body></html>
HTML;
    exit;
}

function serve_static_file(string $absolutePath): void
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
