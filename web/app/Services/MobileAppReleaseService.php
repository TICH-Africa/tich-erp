<?php

namespace App\Services;

use App\Models\MobileAppRelease;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MobileAppReleaseService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * @param  array{platform: string, version_label: string, build_number?: int|null, release_notes?: string|null, external_url?: string|null}  $meta
     */
    public function publish(User $user, array $meta, ?UploadedFile $file = null): MobileAppRelease
    {
        $platform = $meta['platform'];

        if ($platform === MobileAppRelease::PLATFORM_ANDROID && ! $file) {
            throw ValidationException::withMessages([
                'file' => 'Upload an APK file for Android.',
            ]);
        }

        if ($platform === MobileAppRelease::PLATFORM_IOS && ! $file && empty($meta['external_url'])) {
            throw ValidationException::withMessages([
                'external_url' => 'Provide an App Store or TestFlight link, or upload an IPA for enterprise distribution.',
            ]);
        }

        return DB::transaction(function () use ($user, $meta, $file, $platform) {
            MobileAppRelease::query()
                ->where('platform', $platform)
                ->where('is_published', true)
                ->update(['is_published' => false]);

            $storagePath = null;
            $originalName = null;
            $fileSize = null;

            if ($file) {
                $this->assertValidUpload($platform, $file);
                $originalName = $file->getClientOriginalName();
                $storagePath = $file->storeAs(
                    'mobile-releases/'.$platform,
                    $this->buildStoredName($platform, $meta['version_label'], $file),
                    'local'
                );
                $fileSize = $file->getSize() ?: Storage::disk('local')->size($storagePath);
            }

            $release = MobileAppRelease::query()->create([
                'platform' => $platform,
                'version_label' => $meta['version_label'],
                'build_number' => $meta['build_number'] ?? null,
                'storage_path' => $storagePath,
                'original_filename' => $originalName,
                'file_size' => $fileSize,
                'external_url' => $meta['external_url'] ?? null,
                'release_notes' => $meta['release_notes'] ?? null,
                'is_published' => true,
                'uploaded_by_user_id' => $user->id,
            ]);

            $this->pruneOldUnpublished($platform);

            $this->auditService->log(
                'ict.mobile_app.published',
                'mobile_app_releases',
                $release->id,
                null,
                [
                    'platform' => $platform,
                    'version_label' => $release->version_label,
                    'build_number' => $release->build_number,
                    'has_file' => $storagePath !== null,
                    'external_url' => $release->external_url,
                ],
                null,
                'success',
                $user->id,
                request()
            );

            return $release;
        });
    }

    public function unpublish(MobileAppRelease $release, User $user): void
    {
        $release->update(['is_published' => false]);

        $this->auditService->log(
            'ict.mobile_app.unpublished',
            'mobile_app_releases',
            $release->id,
            ['is_published' => true],
            ['is_published' => false],
            null,
            'success',
            $user->id,
            request()
        );
    }

    protected function assertValidUpload(string $platform, UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $maxKb = 262144; // 256 MB

        if ($platform === MobileAppRelease::PLATFORM_ANDROID) {
            if ($ext !== 'apk') {
                throw ValidationException::withMessages([
                    'file' => 'Android builds must be an .apk file for direct download.',
                ]);
            }
        } else {
            if (! in_array($ext, ['ipa', 'zip'], true)) {
                throw ValidationException::withMessages([
                    'file' => 'iOS packages must be .ipa (or .zip containing an IPA).',
                ]);
            }
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => 'The file is too large. Maximum size is 256 MB.',
            ]);
        }
    }

    protected function buildStoredName(string $platform, string $version, UploadedFile $file): string
    {
        $safeVersion = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $version) ?: 'release';
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

        return $platform.'-'.$safeVersion.'-'.now()->format('YmdHis').'.'.$ext;
    }

    protected function pruneOldUnpublished(string $platform): void
    {
        $stale = MobileAppRelease::query()
            ->where('platform', $platform)
            ->where('is_published', false)
            ->orderByDesc('created_at')
            ->skip(5)
            ->take(50)
            ->get();

        foreach ($stale as $row) {
            if ($row->storage_path) {
                Storage::disk('local')->delete($row->storage_path);
            }
            $row->delete();
        }
    }
}