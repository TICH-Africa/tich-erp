<?php

/**
 * tich.africa document-root bridge (HostPinnacle / cPanel).
 *
 * Domain root is fixed at public_html, while Laravel lives in the git clone:
 *   /home…/tichafri/tich-erp/web
 *
 * This file is copied to public_html/index.php on each cPanel Git deploy.
 */

declare(strict_types=1);

$candidates = [
    // Preferred: sibling of public_html (standard HostPinnacle layout)
    dirname(__DIR__).'/tich-erp/web',
    // Committed production path
    '/home3/tichafri/tich-erp/web',
    '/home2/tichafri/tich-erp/web',
    '/home/tichafri/tich-erp/web',
];

// Optional override: one-line absolute path to Laravel web/ root
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
        'Checked paths: '.implode(', ', $candidates).'. Confirm the Git clone is at …/tich-erp and contains web/artisan.',
        $candidates
    );
}

$laravelPublic = $appPath.'/public';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = rawurldecode((string) (parse_url($requestUri, PHP_URL_PATH) ?: '/'));

// Fallback static serving when css/js/images/storage symlinks are missing in public_html.
if ($requestPath !== '/' && ! str_contains($requestPath, '..')) {
    $relative = ltrim($requestPath, '/');
    $assetFile = $laravelPublic.'/'.$relative;

    if (is_file($assetFile)) {
        serve_static_file($assetFile);
        exit;
    }
}

$autoload = $appPath.'/vendor/autoload.php';
if (! is_file($autoload)) {
    bootstrap_fail(
        'Composer dependencies missing',
        'Run deploy again so composer install completes, or SSH/cPanel Terminal: cd '.$appPath.' && composer install --no-dev',
        [$appPath]
    );
}

if (! is_file($appPath.'/.env') && ! is_file($appPath.'/bootstrap/cache/config.php')) {
    bootstrap_fail(
        'Missing .env',
        'Create '.$appPath.'/.env from .env.example, set APP_KEY / DB_* / APP_URL=https://tich.africa, then: php artisan key:generate',
        [$appPath]
    );
}

// Hand off to Laravel's real public front controller (correct __DIR__ paths).
require $laravelPublic.'/index.php';

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
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TICH deploy error</title>
  <style>
    body{font-family:system-ui,sans-serif;max-width:42rem;margin:3rem auto;padding:0 1rem;color:#1f2933;line-height:1.5}
    code,pre{background:#f5f6f6;padding:.2rem .4rem;border-radius:4px}
    pre{padding:1rem;overflow:auto;white-space:pre-wrap}
    h1{font-size:1.35rem;color:#c53030}
  </style>
</head>
<body>
  <h1>{$titleHtml}</h1>
  <p>{$hintHtml}</p>
  <p>In cPanel: <strong>Git Version Control → Update from Remote → Deploy HEAD Commit</strong>, then open <code>deploy/cpanel/last-asset-sync.log</code>.</p>
  <pre>{$triedHtml}</pre>
</body>
</html>
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

    $mime = $types[$ext] ?? 'application/octet-stream';
    header('Content-Type: '.$mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: '.(string) filesize($absolutePath));

    if (in_array($ext, ['css', 'js', 'mjs', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'woff', 'woff2', 'ico'], true)) {
        header('Cache-Control: public, max-age=604800');
    }

    readfile($absolutePath);
}
