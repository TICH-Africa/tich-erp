<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MobileAppReleaseService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

class PublishMobileAppReleaseCommand extends Command
{
    protected $signature = 'mobile-app:publish
        {platform : android or ios}
        {version : e.g. 1.0.0}
        {--build= : Build number}
        {--apk= : Path to APK (Android)}
        {--ipa= : Path to IPA (iOS)}
        {--url= : External download URL (iOS App Store / TestFlight, optional)}
        {--notes= : Release notes text, or path to a .txt file}
        {--user= : User ID for audit (defaults to first active user with ict.read)}';

    protected $description = 'Publish a mobile app release on /mobile-app (same as ICT Mobile app releases UI)';

    public function handle(MobileAppReleaseService $releases): int
    {
        if (! Schema::hasTable('mobile_app_releases')) {
            $this->error('Table mobile_app_releases is missing. Run migrations or production-patches.sql first.');

            return self::FAILURE;
        }

        $platform = strtolower($this->argument('platform'));
        if (! in_array($platform, ['android', 'ios'], true)) {
            $this->error('Platform must be android or ios.');

            return self::FAILURE;
        }

        $user = $this->resolveUser();
        if (! $user) {
            $this->error('No user found for audit. Pass --user=<id>.');

            return self::FAILURE;
        }

        $notes = $this->resolveNotes();
        $file = null;
        if ($platform === 'android') {
            $path = $this->option('apk');
            if (! $path || ! is_readable($path)) {
                $this->error('Android publish requires --apk=/path/to/app.apk (not .aab)');

                return self::FAILURE;
            }
            $file = new UploadedFile($path, basename($path), null, null, true);
        } else {
            $ipa = $this->option('ipa');
            if ($ipa && is_readable($ipa)) {
                $file = new UploadedFile($ipa, basename($ipa), null, null, true);
            } elseif (! $this->option('url')) {
                $this->error('iOS publish requires --url= or --ipa=.');

                return self::FAILURE;
            }
        }

        $release = $releases->publish($user, [
            'platform' => $platform,
            'version_label' => $this->argument('version'),
            'build_number' => $this->option('build') ? (int) $this->option('build') : null,
            'release_notes' => $notes,
            'external_url' => $this->option('url'),
        ], $file);

        $this->info("Published {$platform} {$release->version_label} (id {$release->id}).");
        $this->line(route('mobile-app.index'));

        return self::SUCCESS;
    }

    protected function resolveUser(): ?User
    {
        if ($id = $this->option('user')) {
            return User::query()->find($id);
        }

        return User::query()->where('is_active', 1)->orderBy('id')->first();
    }

    protected function resolveNotes(): ?string
    {
        $notes = $this->option('notes');
        if ($notes === null || $notes === '') {
            $default = dirname(base_path()).'/mobile/tich/releases/v1.0.0/release-notes.txt';
            if (is_readable($default)) {
                return trim((string) file_get_contents($default));
            }

            return null;
        }

        if (is_file($notes) && str_ends_with(strtolower($notes), '.txt')) {
            return trim((string) file_get_contents($notes));
        }

        return $notes;
    }
}