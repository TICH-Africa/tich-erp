<?php

/**
 * One-shot production fix: regenerate Composer autoload WITHOUT require-dev
 * (mockery/phpunit). Upload to public_html and open once, then delete.
 *
 *   https://tich.africa/tich-fix-autoload.php
 *
 * Does NOT delete vendor unless install is confirmed possible (HOME set).
 */

ini_set('display_errors', '1');
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
set_time_limit(600);

header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>TICH fix autoload</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:52rem;margin:2rem auto;padding:0 1rem;line-height:1.45}';
echo 'pre{background:#f5f6f6;padding:.75rem;overflow:auto;white-space:pre-wrap}.ok{color:#166534}.bad{color:#b91c1c}</style></head><body>';
echo '<h1>Fix Composer autoload (--no-dev)</h1>';

$appPath = '/home3/tichafri/tich-erp/web';
$homeDir = '/home3/tichafri';
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

$composerBin = null;
foreach (['/opt/cpanel/composer/bin/composer', '/usr/local/bin/composer', '/usr/bin/composer'] as $candidate) {
    if (is_file($candidate)) {
        $composerBin = $candidate;
        break;
    }
}

$composerHome = $homeDir.'/.composer';
if (! is_dir($composerHome)) {
    @mkdir($composerHome, 0755, true);
}

putenv('HOME='.$homeDir);
putenv('COMPOSER_HOME='.$composerHome);
putenv('COMPOSER_MEMORY_LIMIT=512M');
$_ENV['HOME'] = $homeDir;
$_ENV['COMPOSER_HOME'] = $composerHome;

echo '<ul>';
echo '<li>App: <code>'.htmlspecialchars($appPath, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '<li>PHP: <code>'.htmlspecialchars((string) $phpBin, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '<li>Composer: <code>'.htmlspecialchars((string) $composerBin, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '<li>HOME: <code>'.htmlspecialchars($homeDir, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '<li>COMPOSER_HOME: <code>'.htmlspecialchars($composerHome, ENT_QUOTES, 'UTF-8').'</code></li>';
echo '<li>vendor/autoload.php: <strong class="'.(is_file($appPath.'/vendor/autoload.php') ? 'ok' : 'bad').'">'.(is_file($appPath.'/vendor/autoload.php') ? 'present' : 'MISSING').'</strong></li>';
echo '</ul>';

if ($phpBin === null || $composerBin === null) {
    echo '<p class="bad">Cannot find ea-php82 or composer. In cPanel Terminal run:</p>';
    echo '<pre>export HOME=/home3/tichafri
export COMPOSER_HOME=/home3/tichafri/.composer
cd ~/tich-erp/web
ea-php82 /opt/cpanel/composer/bin/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction</pre>';
    echo '</body></html>';
    exit;
}

function tich_run($label, $cmd)
{
    echo '<h2>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</h2>';
    echo '<pre>'.htmlspecialchars($cmd, ENT_QUOTES, 'UTF-8').'</pre>';
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);
    echo '<pre>'.htmlspecialchars(implode("\n", $output), ENT_QUOTES, 'UTF-8').'</pre>';
    echo '<p>Exit code: <strong>'.(int) $exitCode.'</strong></p>';

    return $exitCode;
}

function tich_autoload_has_dev($appPath)
{
    foreach ([
        'autoload_files.php',
        'autoload_psr4.php',
        'autoload_classmap.php',
        'autoload_static.php',
        'installed.php',
        'installed.json',
    ] as $name) {
        $path = $appPath.'/vendor/composer/'.$name;
        if (! is_file($path)) {
            continue;
        }
        $contents = (string) file_get_contents($path);
        if (str_contains($contents, 'mockery/mockery') || str_contains($contents, 'phpunit/phpunit')) {
            return true;
        }
    }

    return false;
}

$envPrefix = 'HOME='.escapeshellarg($homeDir)
    .' COMPOSER_HOME='.escapeshellarg($composerHome)
    .' COMPOSER_MEMORY_LIMIT=512M';

$vendorMissing = ! is_file($appPath.'/vendor/autoload.php');
$hadDev = (! $vendorMissing) && tich_autoload_has_dev($appPath);

echo '<p>Dev packages in autoload before: <strong class="'.($hadDev ? 'bad' : 'ok').'">'.($hadDev ? 'YES' : 'no').'</strong></p>';

if ($vendorMissing) {
    echo '<p class="bad"><strong>vendor is missing</strong> (likely wiped by a previous failed fix). Running full install…</p>';
    $installCmd = $envPrefix
        .' '.escapeshellarg($phpBin).' '.escapeshellarg($composerBin)
        .' install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress -d '.escapeshellarg($appPath).' 2>&1';
    $exitCode = tich_run('composer install --no-dev', $installCmd);
} else {
    $dumpCmd = $envPrefix
        .' '.escapeshellarg($phpBin).' '.escapeshellarg($composerBin)
        .' dump-autoload --no-dev --optimize --no-interaction -d '.escapeshellarg($appPath).' 2>&1';
    $exitCode = tich_run('composer dump-autoload --no-dev', $dumpCmd);

    if ($exitCode !== 0 || tich_autoload_has_dev($appPath)) {
        echo '<p>Dump did not fully clear require-dev refs. Running install --no-dev (keeps vendor; does not rm -rf)…</p>';
        $installCmd = $envPrefix
            .' '.escapeshellarg($phpBin).' '.escapeshellarg($composerBin)
            .' install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress -d '.escapeshellarg($appPath).' 2>&1';
        $exitCode = tich_run('composer install --no-dev', $installCmd);
    }
}

$vendorOk = is_file($appPath.'/vendor/autoload.php');
$stillDev = $vendorOk && tich_autoload_has_dev($appPath);

echo '<p>vendor/autoload.php: <strong class="'.($vendorOk ? 'ok' : 'bad').'">'.($vendorOk ? 'present' : 'MISSING').'</strong></p>';
echo '<p>Dev refs after: <strong class="'.($stillDev ? 'bad' : 'ok').'">'.($stillDev ? 'STILL YES' : 'cleared').'</strong></p>';

if ($vendorOk && ! $stillDev && $exitCode === 0) {
    // Quick boot smoke test
    try {
        require $appPath.'/vendor/autoload.php';
        echo '<p class="ok"><strong>Autoload loads.</strong> Open <a href="/">https://tich.africa/</a> then <strong>delete this file</strong>.</p>';
    } catch (Throwable $e) {
        echo '<p class="bad">Autoload still throws: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</p>';
    }
} else {
    echo '<p class="bad"><strong>Not fully fixed.</strong> In cPanel → Terminal run:</p>';
    echo '<pre>export HOME=/home3/tichafri
export COMPOSER_HOME=/home3/tichafri/.composer
cd /home3/tichafri/tich-erp/web
/usr/local/bin/ea-php82 /opt/cpanel/composer/bin/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction</pre>';
}

echo '</body></html>';
