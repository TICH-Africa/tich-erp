<?php

/**
 * Fix production media paths (no Terminal required).
 *
 * Ensures:
 *   web/public/storage → web/storage/app/public
 *   public_html/storage → web/storage/app/public
 * and reports whether a sample upload URL would resolve.
 *
 * Upload to: public_html/tich-fix-storage.php
 * Visit:     https://tich.africa/tich-fix-storage.php?key=tich-storage-2026
 * DELETE after use.
 */

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

if (($_GET['key'] ?? '') !== 'tich-storage-2026') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$appPath = '/home3/tichafri/tich-erp/web';
$docroot = '/home3/tichafri/public_html';
$storageReal = $appPath.'/storage/app/public';
$publicStorage = $appPath.'/public/storage';
$docrootStorage = $docroot.'/storage';

echo '<h1>TICH storage fix</h1><pre>';

function link_or_report(string $link, string $target): void
{
    if (! is_dir($target) && ! is_link($target)) {
        if (! @mkdir($target, 0755, true) && ! is_dir($target)) {
            echo "FAIL mkdir: {$target}\n";

            return;
        }
    }

    if (is_link($link) || is_file($link) || is_dir($link)) {
        if (is_link($link)) {
            @unlink($link);
        } else {
            // Do not recursively delete a real directory of uploaded copies blindly.
            // Rename aside so we can replace with a symlink.
            $backup = $link.'.bak-'.date('YmdHis');
            @rename($link, $backup);
            echo "Moved existing {$link} -> {$backup}\n";
        }
    }

    if (@symlink($target, $link)) {
        echo "OK symlink: {$link} -> {$target}\n";
    } else {
        echo "FAIL symlink: {$link} -> {$target} (host may block symlinks; index.php bridge can still serve files)\n";
    }
}

echo 'storage/app/public exists: '.(is_dir($storageReal) ? 'YES' : 'NO')."\n";
if (! is_dir($storageReal)) {
    @mkdir($storageReal, 0755, true);
    echo "Created {$storageReal}\n";
}

$uploadCount = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($storageReal, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $uploadCount++;
    }
}
echo "Files under storage/app/public: {$uploadCount}\n\n";

link_or_report($publicStorage, $storageReal);
link_or_report($docrootStorage, $storageReal);

echo "\nBridge check (sample):\n";
$sample = null;
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $sample = $file->getPathname();
        break;
    }
}

if ($sample) {
    $rel = ltrim(str_replace('\\', '/', substr($sample, strlen($storageReal))), '/');
    $url = 'https://tich.africa/storage/'.$rel;
    echo "Sample file: {$sample}\n";
    echo "Expected URL: {$url}\n";
    echo "Via public/storage: ".(is_file($publicStorage.'/'.$rel) ? 'YES' : 'NO')."\n";
    echo "Via docroot/storage: ".(is_file($docrootStorage.'/'.$rel) ? 'YES' : 'NO')."\n";
} else {
    echo "No uploaded files yet - upload an image in admin, then re-run this check.\n";
}

echo "\nAlso ensure web/.env has:\n";
echo "  APP_URL=https://tich.africa\n";
echo "  FORCE_HTTPS=true\n";
echo "  BROADCAST_CONNECTION=null\n";

echo '</pre>';
echo '<p style="color:#b91c1c"><strong>DELETE public_html/tich-fix-storage.php now.</strong></p>';
