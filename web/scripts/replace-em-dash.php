<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$skipDirs = ['vendor', 'node_modules', '.git', 'bootstrap/cache', 'storage/framework', 'storage/logs'];
$skipExt = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2', 'pdf', 'zip', 'gz', 'lock'];

$from = "\u{2014}"; // em dash
$to = '-';

$filesChanged = 0;
$replacements = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $path = str_replace('\\', '/', $file->getPathname());

    foreach ($skipDirs as $skip) {
        if (str_contains($path, '/'.$skip.'/')) {
            continue 2;
        }
    }

    $ext = strtolower($file->getExtension());
    if (in_array($ext, $skipExt, true)) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false || ! str_contains($content, $from)) {
        continue;
    }

    $count = 0;
    $new = str_replace($from, $to, $content, $count);

    if ($count > 0) {
        file_put_contents($path, $new);
        $filesChanged++;
        $replacements += $count;
        $relative = str_replace($root.'/', '', $path);
        echo $relative.' ('.$count.')'.PHP_EOL;
    }
}

echo 'Done: '.$filesChanged.' files, '.$replacements.' replacements'.PHP_EOL;
