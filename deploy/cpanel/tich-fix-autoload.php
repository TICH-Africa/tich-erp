<?php

/**
 * One-shot production fix: regenerate Composer autoload WITHOUT require-dev
 * (mockery/phpunit). Upload to public_html and open once, then delete.
 *
 *   https://tich.africa/tich-fix-autoload.php
 */

ini_set('display_errors', '1');
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
set_time_limit(300);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>TICH fix autoload</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:52rem;margin:2rem auto;padding:0 1rem;line-height:1.45}';
echo 'pre{background:#f5f6f6;padding:.75rem;overflow:auto;white-space:pre-wrap}.ok{color:#166534}.bad{color:#b91c1c}</style></head><body>';
echo '<h1>Fix Composer autoload (--no-dev)</h1>';

$appPath = '/home3/tichafri/tich-erp/web';
if (! is_dir($appPath) || ! is_file($appPath.'/composer.json')) {
    echo '<p class="bad">Laravel app not found at '.$appPath.'</p></body></html>';
    exit;
}

$phpBin = null;
foreach (['/usr/local/bin/ea-php82', '/usr/local/bin/ea-php83', '/usr/local/bin/ea-php81', '/usr/bin/php'] as $candidate) {
    if (is_executable($candidate)) {
        $phpBin = $candidate;
        break;
    }
}
$composerBin = '/opt/cpanel/composer/bin/composer';
if (! is_file($composerBin)) {
    $which = trim((string) shell_exec('command -v composer 2>/dev/null'));
    $composerBin = $which !== '' ? $which : null;
}

echo '<ul>';
echo '<li>App: <code>'.htmlspecialchars($appPath, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '<li>PHP: <code>'.htmlspecialchars((string) $phpBin, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '<li>Composer: <code>'.htmlspecialchars((string) $composerBin, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '</ul>';

if ($phpBin === null || $composerBin === null) {
    echo '<p class="bad">Cannot find ea-php82 or composer. In cPanel Terminal run:</p>';
    echo '<pre>cd ~/tich-erp/web
ea-php82 /opt/cpanel/composer/bin/composer dump-autoload --no-dev --optimize</pre>';
    echo '</body></html>';
    exit;
}

$autoloadFiles = $appPath.'/vendor/composer/autoload_files.php';
$before = is_file($autoloadFiles) ? (string) file_get_contents($autoloadFiles) : '';
$hadMockery = str_contains($before, 'mockery/mockery');
echo '<p>Mockery in autoload_files.php before: <strong class="'.($hadMockery ? 'bad' : 'ok').'">'.($hadMockery ? 'YES (broken)' : 'no').'</strong></p>';

$cmd = 'cd '.escapeshellarg($appPath)
    .' && COMPOSER_MEMORY_LIMIT=512M '.escapeshellarg($phpBin).' '.escapeshellarg($composerBin)
    .' dump-autoload --no-dev --optimize --no-interaction 2>&1';

echo '<h2>Running</h2><pre>'.htmlspecialchars($cmd, ENT_QUOTES, 'UTF-8').'</pre>';
$output = [];
$exitCode = 0;
exec($cmd, $output, $exitCode);
echo '<pre>'.htmlspecialchars(implode("\n", $output), ENT_QUOTES, 'UTF-8').'</pre>';
echo '<p>Exit code: <strong>'.(int) $exitCode.'</strong></p>';

$after = is_file($autoloadFiles) ? (string) file_get_contents($autoloadFiles) : '';
$stillMockery = str_contains($after, 'mockery/mockery');
echo '<p>Mockery in autoload_files.php after: <strong class="'.($stillMockery ? 'bad' : 'ok').'">'.($stillMockery ? 'STILL YES' : 'cleared').'</strong></p>';

if ($exitCode !== 0 || $stillMockery) {
    echo '<h2>Fallback: full composer install --no-dev</h2>';
    $installCmd = 'cd '.escapeshellarg($appPath)
        .' && rm -rf vendor'
        .' && COMPOSER_MEMORY_LIMIT=512M '.escapeshellarg($phpBin).' '.escapeshellarg($composerBin)
        .' install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress 2>&1';
    echo '<pre>'.htmlspecialchars($installCmd, ENT_QUOTES, 'UTF-8').'</pre>';
    $output = [];
    $exitCode = 0;
    exec($installCmd, $output, $exitCode);
    echo '<pre>'.htmlspecialchars(implode("\n", $output), ENT_QUOTES, 'UTF-8').'</pre>';
    echo '<p>Install exit: <strong>'.(int) $exitCode.'</strong></p>';

    $after = is_file($autoloadFiles) ? (string) file_get_contents($autoloadFiles) : '';
    $stillMockery = str_contains($after, 'mockery/mockery');
    echo '<p>Mockery after install: <strong class="'.($stillMockery ? 'bad' : 'ok').'">'.($stillMockery ? 'STILL YES' : 'cleared').'</strong></p>';
}

if (! $stillMockery && $exitCode === 0) {
    echo '<p class="ok"><strong>Fixed.</strong> Open <a href="/">https://tich.africa/</a> then <strong>delete this file</strong> from public_html.</p>';
} else {
    echo '<p class="bad"><strong>Still broken.</strong> Use cPanel Terminal with the commands above, or re-run Git Deploy.</p>';
}

echo '</body></html>';
