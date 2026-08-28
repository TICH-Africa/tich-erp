<?php

/**
 * public_html/index.php - bridge to Laravel at tich-erp/web
 * Paste this file into /home3/tichafri/public_html/index.php
 *
 * Serves static assets (css/js/images) and uploaded media (/storage/…)
 * even when public_html/storage or public/storage symlinks are missing.
 */

$appPath = '/home3/tichafri/tich-erp/web';
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

        $faviconExt = strtolower(pathinfo($faviconFile, PATHINFO_EXTENSION));
        header('Content-Type: '.($faviconExt === 'ico' ? 'image/x-icon' : 'image/png'));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=604800');
        header('Content-Length: '.(string) filesize($faviconFile));
        readfile($faviconFile);
        exit;
    }
}

if ($requestPath !== '/' && strpos($requestPath, '..') === false) {
    $relative = ltrim($requestPath, '/');

    if ($relative !== '' && substr(strtolower($relative), -4) !== '.php') {
        $candidates = [];

        // Preferred: Laravel public path (includes public/storage symlink).
        $candidates[] = $laravelPublic.'/'.$relative;

        // Fallback for uploads: map /storage/foo → storage/app/public/foo
        if (str_starts_with($relative, 'storage/')) {
            $candidates[] = $storagePublic.'/'.substr($relative, strlen('storage/'));
        }

        foreach ($candidates as $assetFile) {
            if (! is_file($assetFile)) {
                continue;
            }

            $ext = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
            $types = [
                'css' => 'text/css; charset=UTF-8',
                'js' => 'application/javascript; charset=UTF-8',
                'mjs' => 'application/javascript; charset=UTF-8',
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
                'json' => 'application/json; charset=UTF-8',
                'pdf' => 'application/pdf',
            ];

            header('Content-Type: '.($types[$ext] ?? 'application/octet-stream'));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: public, max-age=604800');
            header('Content-Length: '.(string) filesize($assetFile));
            readfile($assetFile);
            exit;
        }
    }
}

require $laravelPublic.'/index.php';
