<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MobileAppRelease;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileAppDownloadController extends Controller
{
    public function platform(string $platform): StreamedResponse|Response
    {
        abort_unless(in_array($platform, [MobileAppRelease::PLATFORM_ANDROID, MobileAppRelease::PLATFORM_IOS], true), 404);

        if (! Schema::hasTable('mobile_app_releases')) {
            abort(404);
        }

        $release = MobileAppRelease::currentForPlatform($platform);

        if (! $release || ! $release->hasDownloadableFile()) {
            abort(404, 'No install file is available for this platform.');
        }

        return Storage::disk('local')->download(
            $release->storage_path,
            $release->downloadFilename(),
            ['Content-Type' => $this->contentTypeFor($platform)]
        );
    }

    private function contentTypeFor(string $platform): string
    {
        return $platform === MobileAppRelease::PLATFORM_ANDROID
            ? 'application/vnd.android.package-archive'
            : 'application/octet-stream';
    }
}