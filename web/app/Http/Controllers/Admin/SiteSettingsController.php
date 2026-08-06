<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portal\CarouselSlide;
use App\Models\Site\ContactChannel;
use App\Models\Site\SocialLink;
use App\Services\AuditService;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function __construct(
        protected SiteSettingsService $settings,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $panel = $this->resolvePanel($request->string('panel')->toString());

        return view('site-settings.index', [
            'panel' => $panel,
            'siteMeta' => $this->settings->siteMeta(),
            'slides' => CarouselSlide::query()->orderBy('display_order')->orderBy('id')->get(),
            'contacts' => ContactChannel::query()->orderByDesc('is_primary')->orderBy('display_order')->get(),
            'socialLinks' => SocialLink::query()->orderBy('display_order')->get(),
            'channelTypes' => ['email', 'phone', 'physical_address', 'fax', 'social_media'],
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:300'],
            'short_name' => ['required', 'string', 'max:120'],
            'brand_name' => ['required', 'string', 'max:120'],
            'brand_tagline' => ['nullable', 'string', 'max:200'],
            'tagline' => ['nullable', 'string', 'max:300'],
            'copyright' => ['nullable', 'string', 'max:300'],
            'website' => ['nullable', 'string', 'max:200'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $userId = (int) $request->user()->id;
        $previousLogo = $this->settings->get('site.logo_path');

        $this->settings->set('site.institution_name', $validated['institution_name'], $userId, [
            'group_name' => 'identity',
            'label' => 'Institution name',
        ]);
        $this->settings->set('site.short_name', $validated['short_name'], $userId, [
            'group_name' => 'identity',
            'label' => 'Short name',
        ]);
        $this->settings->set('site.brand_name', $validated['brand_name'], $userId, [
            'group_name' => 'identity',
            'label' => 'Navbar brand name',
        ]);
        $this->settings->set('site.brand_tagline', $validated['brand_tagline'] ?? '', $userId, [
            'group_name' => 'identity',
            'label' => 'Navbar tagline',
        ]);
        $this->settings->set('site.tagline', $validated['tagline'] ?? '', $userId, [
            'group_name' => 'identity',
            'label' => 'Site tagline',
        ]);
        $this->settings->set('site.copyright', $validated['copyright'] ?? '', $userId, [
            'group_name' => 'identity',
            'label' => 'Copyright',
        ]);
        $this->settings->set('site.website', $validated['website'] ?? '', $userId, [
            'group_name' => 'identity',
            'label' => 'Website',
        ]);

        if ($request->boolean('remove_logo') && $previousLogo) {
            $this->settings->deletePublicAsset($previousLogo);
            $this->settings->set('site.logo_path', null, $userId, [
                'group_name' => 'identity',
                'label' => 'Site logo',
                'value_type' => 'file_path',
            ]);
        } elseif ($request->hasFile('logo')) {
            $logoPath = $this->settings->storePublicUpload($request->file('logo'), 'site/logo');
            if ($logoPath) {
                if ($previousLogo && $previousLogo !== $logoPath) {
                    $this->settings->deletePublicAsset($previousLogo);
                }
                $this->settings->set('site.logo_path', $logoPath, $userId, [
                    'group_name' => 'identity',
                    'label' => 'Site logo',
                    'value_type' => 'file_path',
                ]);
            }
        }

        $this->auditService->log(
            'site_settings.general.updated',
            'site_settings',
            null,
            null,
            ['panel' => 'general'],
            'Site identity settings updated',
            'success',
            $userId,
            $request
        );

        return redirect()
            ->route('site-settings.index', ['panel' => 'general'])
            ->with('status', 'Site identity settings saved.');
    }

    public function storeSlide(Request $request): RedirectResponse
    {
        $validated = $this->validateSlide($request);

        $imagePath = $this->settings->storePublicUpload($request->file('image'), 'site/carousel');

        $slide = CarouselSlide::create([
            ...$validated,
            'image_path' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditService->log(
            'site_settings.hero_slide.created',
            'homepage_carousel_slides',
            $slide->id,
            null,
            $slide->only(['title', 'display_order']),
            'Hero slide created',
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Hero slide added.');
    }

    public function updateSlide(Request $request, CarouselSlide $slide): RedirectResponse
    {
        $validated = $this->validateSlide($request, $slide->id);

        $imagePath = $slide->image_path;
        if ($request->hasFile('image')) {
            $newPath = $this->settings->storePublicUpload($request->file('image'), 'site/carousel');
            if ($newPath) {
                $this->settings->deletePublicAsset($imagePath);
                $imagePath = $newPath;
            }
        }

        $slide->update([
            ...$validated,
            'image_path' => $imagePath,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Hero slide updated.');
    }

    public function destroySlide(Request $request, CarouselSlide $slide): RedirectResponse
    {
        $this->settings->deletePublicAsset($slide->image_path);
        $slide->delete();

        return back()->with('status', 'Hero slide removed.');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channel_type' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:200'],
            'value' => ['required', 'string', 'max:500'],
            'display_value' => ['nullable', 'string', 'max:500'],
            'is_primary' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        ContactChannel::create([
            ...$validated,
            'display_value' => $validated['display_value'] ?: $validated['value'],
            'is_primary' => $request->boolean('is_primary'),
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => 1,
        ]);

        return back()->with('status', 'Contact channel added.');
    }

    public function updateContact(Request $request, ContactChannel $contact): RedirectResponse
    {
        $validated = $request->validate([
            'channel_type' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:200'],
            'value' => ['required', 'string', 'max:500'],
            'display_value' => ['nullable', 'string', 'max:500'],
            'is_primary' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $contact->update([
            ...$validated,
            'display_value' => $validated['display_value'] ?: $validated['value'],
            'is_primary' => $request->boolean('is_primary'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Contact channel updated.');
    }

    public function destroyContact(ContactChannel $contact): RedirectResponse
    {
        $contact->delete();

        return back()->with('status', 'Contact channel removed.');
    }

    public function storeSocialLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'max:50'],
            'display_name' => ['required', 'string', 'max:200'],
            'url' => ['required', 'url', 'max:500'],
            'icon_name' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        SocialLink::create([
            ...$validated,
            'icon_name' => $validated['icon_name'] ?: $validated['platform'],
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => 1,
        ]);

        return back()->with('status', 'Social link added.');
    }

    public function updateSocialLink(Request $request, SocialLink $socialLink): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'max:50'],
            'display_name' => ['required', 'string', 'max:200'],
            'url' => ['required', 'url', 'max:500'],
            'icon_name' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $socialLink->update([
            ...$validated,
            'icon_name' => $validated['icon_name'] ?: $validated['platform'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Social link updated.');
    }

    public function destroySocialLink(SocialLink $socialLink): RedirectResponse
    {
        $socialLink->delete();

        return back()->with('status', 'Social link removed.');
    }

    private function resolvePanel(string $panel): string
    {
        return in_array($panel, ['general', 'hero', 'contact', 'social'], true) ? $panel : 'general';
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSlide(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'image' => [$ignoreId ? 'nullable' : 'nullable', 'image', 'max:5120'],
        ]);
    }
}
