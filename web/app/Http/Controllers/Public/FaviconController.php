<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaviconController extends Controller
{
    public function __invoke(SiteSettingsService $settings): BinaryFileResponse
    {
        $path = $settings->faviconAbsolutePath();

        abort_unless(is_file($path) && filesize($path) > 0, 404);

        return response()->file($path, [
            'Content-Type' => $settings->faviconMimeType(),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
