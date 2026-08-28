<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class PublicAssetSyncService
{
    public function logPath(): string
    {
        return $this->repoRoot().'/deploy/cpanel/last-asset-sync.log';
    }

    public function docrootConfigPath(): string
    {
        return $this->repoRoot().'/deploy/cpanel/docroot.txt';
    }

    public function appPublicPath(): string
    {
        return public_path();
    }

    /**
     * @return array{ok: bool, messages: list<string>}
     */
    public function sync(): array
    {
        $messages = [];
        $log = function (string $message) use (&$messages) {
            $line = '['.now()->format('Y-m-d H:i:s').'] '.$message;
            $messages[] = $line;
            File::ensureDirectoryExists(dirname($this->logPath()));
            File::append($this->logPath(), $line.PHP_EOL);
        };

        File::put($this->logPath(), '');

        $appPublic = $this->appPublicPath();
        $log('Asset sync started');
        $log('Laravel public: '.$appPublic);

        if (! File::isFile($appPublic.'/css/tich-platform.css')) {
            $log('ERROR: Missing '.$appPublic.'/css/tich-platform.css');

            return ['ok' => false, 'messages' => $messages];
        }

        $storageLink = $appPublic.'/storage';
        $storageTarget = storage_path('app/public');
        File::ensureDirectoryExists($storageTarget);

        if (is_link($storageLink)) {
            @unlink($storageLink);
        } elseif (File::exists($storageLink)) {
            // Replace broken/non-link public/storage so uploads resolve.
            if (File::isDirectory($storageLink) && ! is_link($storageLink)) {
                $log('WARN: public/storage is a real directory; leaving it (prefer symlink)');
            } else {
                File::delete($storageLink);
            }
        }

        if (! File::exists($storageLink)) {
            if (@symlink($storageTarget, $storageLink)) {
                $log('Created storage link: '.$storageLink.' -> '.$storageTarget);
            } else {
                $log('WARN: Could not create public/storage symlink - index.php bridge still serves uploads from storage/app/public');
            }
        } else {
            $log('Storage link present: '.$storageLink);
        }

        $docroot = $this->resolveDocroot($log);

        if ($docroot === null) {
            return ['ok' => false, 'messages' => $messages];
        }

        foreach (['css', 'js', 'images'] as $name) {
            $source = $appPublic.'/'.$name;
            if (! File::exists($source)) {
                $log('Skip missing: '.$source);
                continue;
            }

            $this->linkOrCopy($source, $docroot.'/'.$name, $log);
        }

        // Docroot /storage points at the real upload folder (not nested public/storage).
        $this->linkOrCopy($storageTarget, $docroot.'/storage', $log);

        foreach (['favicon.ico', 'favicon.png', 'robots.txt'] as $file) {
            $source = $appPublic.'/'.$file;
            if (File::isFile($source) && filesize($source) > 0) {
                File::copy($source, $docroot.'/'.$file);
                $log('Copied '.$file);
            }
        }

        $cssOk = File::isFile($docroot.'/css/tich-platform.css')
            || is_link($docroot.'/css');
        $jsOk = File::isFile($docroot.'/js/tich-nav.js')
            || is_link($docroot.'/js');

        if (! $cssOk || ! $jsOk) {
            $log('ERROR: css/js still missing in '.$docroot);

            return ['ok' => false, 'messages' => $messages];
        }

        $log('SUCCESS: assets available under '.$docroot);

        return ['ok' => true, 'messages' => $messages];
    }

    /**
     * @param  callable(string): void  $log
     */
    private function resolveDocroot(callable $log): ?string
    {
        $configPath = $this->docrootConfigPath();

        if (File::isFile($configPath)) {
            $configured = trim((string) File::get($configPath));
            if ($configured !== '' && File::isDirectory($configured)) {
                $log('Docroot from docroot.txt: '.$configured);

                return rtrim($configured, '/');
            }

            $log('WARN: docroot.txt path not found: '.$configured);
        } else {
            $log('WARN: Missing '.$configPath.' - create it via File Manager (see deploy/cpanel/docroot.txt.example)');
        }

        $user = get_current_user() ?: 'tichafri';
        $candidates = [
            "/home3/{$user}/public_html",
            "/home3/{$user}/tich.africa/public_html",
            "/home2/{$user}/public_html",
            "/home2/{$user}/tich.africa/public_html",
        ];

        foreach ($candidates as $candidate) {
            if (File::isFile($candidate.'/index.php')) {
                $log('Docroot auto-detected: '.$candidate);

                return $candidate;
            }
        }

        $log('ERROR: Could not find document root. Create deploy/cpanel/docroot.txt with one line: full path to public_html');

        return null;
    }

    /**
     * @param  callable(string): void  $log
     */
    private function linkOrCopy(string $source, string $target, callable $log): void
    {
        if (is_link($target)) {
            @unlink($target);
        } elseif (File::isDirectory($target)) {
            File::deleteDirectory($target);
        } elseif (File::exists($target)) {
            File::delete($target);
        }

        if (@symlink($source, $target)) {
            $log('Linked '.basename($target).' -> '.$source);

            return;
        }

        $log('Symlink failed for '.basename($target).', copying instead');

        if (File::isDirectory($source)) {
            File::copyDirectory($source, $target);
        } else {
            File::ensureDirectoryExists(dirname($target));
            File::copy($source, $target);
        }

        $log('Copied '.basename($target));
    }

    private function repoRoot(): string
    {
        return dirname(base_path());
    }
}
