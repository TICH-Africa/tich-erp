<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use App\Models\MobileAppRelease;
use App\Services\MobileAppReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MobileAppReleaseController extends Controller
{
    public function __construct(protected MobileAppReleaseService $releases) {}

    public function index(): View
    {
        $android = null;
        $ios = null;
        $history = collect();

        if (Schema::hasTable('mobile_app_releases')) {
            $android = MobileAppRelease::currentForPlatform(MobileAppRelease::PLATFORM_ANDROID);
            $ios = MobileAppRelease::currentForPlatform(MobileAppRelease::PLATFORM_IOS);
            $history = MobileAppRelease::query()->orderByDesc('created_at')->limit(20)->get();
        }

        return view('ict.mobile-app-releases.index', [
            'android' => $android,
            'ios' => $ios,
            'history' => $history,
            'publicUrl' => route('mobile-app.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'in:android,ios'],
            'version_label' => ['required', 'string', 'max:50'],
            'build_number' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'release_notes' => ['nullable', 'string', 'max:5000'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'file' => ['nullable', 'file', 'max:262144'],
        ]);

        $this->releases->publish(
            $request->user(),
            [
                'platform' => $validated['platform'],
                'version_label' => $validated['version_label'],
                'build_number' => $validated['build_number'] ?? null,
                'release_notes' => $validated['release_notes'] ?? null,
                'external_url' => $validated['external_url'] ?? null,
            ],
            $request->file('file')
        );

        return redirect()
            ->route('ict.mobile-app.index')
            ->with('status', 'Mobile release published on '.$validated['platform'].'. It is now live on '.route('mobile-app.index').'.');
    }

    public function destroy(MobileAppRelease $release): RedirectResponse
    {
        $this->releases->unpublish($release, request()->user());

        return redirect()
            ->route('ict.mobile-app.index')
            ->with('status', 'Release removed from the public download page.');
    }
}