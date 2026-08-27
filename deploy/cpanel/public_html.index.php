<?php

/**
 * public_html/index.php — bridge to Laravel at tich-erp/web
 * Paste this file into /home3/tichafri/public_html/index.php
 */

$appPath = '/home3/tichafri/tich-erp/web';
$laravelPublic = $appPath.'/public';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = rawurldecode((string) (parse_url($requestUri, PHP_URL_PATH) ?: '/'));

// Serve css/js/images/storage from Laravel public/ without booting the app.
if ($requestPath !== '/' && strpos($requestPath, '..') === false) {
    $relative = ltrim($requestPath, '/');
    if (substr(strtolower($relative), -4) !== '.php') {
        $assetFile = $laravelPublic.'/'.$relative;
        if (is_file($assetFile)) {
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
            header('Content-Length: '.(string) filesize($assetFile));
            readfile($assetFile);
            exit;
        }
    }
}

require $laravelPublic.'/index.php';
