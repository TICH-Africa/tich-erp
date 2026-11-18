<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MobileAppRelease;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MobileAppPageController extends Controller
{
    public function index(): View
    {
        $android = null;
        $ios = null;

        if (Schema::hasTable('mobile_app_releases')) {
            $android = MobileAppRelease::currentForPlatform(MobileAppRelease::PLATFORM_ANDROID);
            $ios = MobileAppRelease::currentForPlatform(MobileAppRelease::PLATFORM_IOS);
        }

        return view('pages.mobile-app', [
            'android' => $android,
            'ios' => $ios,
        ]);
    }
}