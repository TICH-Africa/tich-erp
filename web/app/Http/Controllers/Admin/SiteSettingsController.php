<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portal\CarouselSlide;
use App\Models\Site\ContactChannel;
use App\Models\Site\SocialLink;
use App\Services\AuditService;
use App\Services\SiteSettingsService;
use App\Services\StoredFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function __construct(
        protected SiteSettingsService $settings,
        protected AuditService $auditService,
        protected StoredFileService $files,
    ) {}

    public function index(Request $request): View
    {
        $panel = $this->resolvePanel($request->string('panel')->toString());

        return view('site-settings.index', [
            'panel' => $panel,
            'siteMeta' => $this->settings->siteMeta(),
            'slides' => CarouselSlide::query()->orderBy('display_order')->orderBy('id')->get(),
            'contacts' => ContactChannel::query()->orderBy('display_order')->orderBy('id')->get(),
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
            $this->files->delete($previousLogo, 'public');
            $this->settings->set('site.logo_path', null, $userId, [
                'group_name' => 'identity',
                'label' => 'Site logo',
                'value_type' => 'file_path',
            ]);
        } elseif ($request->hasFile('logo')) {
            $logoPath = $this->files->replace($previousLogo, $request->file('logo'), 'site/logo', 'public', null, true);
            $this->settings->set('site.logo_path', $logoPath, $userId, [
                'group_name' => 'identity',
                'label' => 'Site logo',
                'value_type' => 'file_path',
            ]);
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
            'display_order' => (int) CarouselSlide::query()->max('display_order') + 1,
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
            $imagePath = $this->files->replace($slide->image_path, $request->file('image'), 'site/carousel', 'public', null, true);
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
        ]);

        ContactChannel::create([
            ...$validated,
            'display_value' => $validated['display_value'] ?: $validated['value'],
            'is_primary' => $request->boolean('is_primary'),
            'display_order' => (int) ContactChannel::query()->max('display_order') + 1,
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
        ]);

        SocialLink::create([
            ...$validated,
            'icon_name' => $validated['icon_name'] ?: $validated['platform'],
            'display_order' => (int) SocialLink::query()->max('display_order') + 1,
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

    public function reorderSlides(Request $request): JsonResponse
    {
        return $this->reorderByDisplayOrder($request, CarouselSlide::class, 'homepage_carousel_slides', 'Invalid slide order.');
    }

    public function reorderContacts(Request $request): JsonResponse
    {
        return $this->reorderByDisplayOrder($request, ContactChannel::class, 'contact_channels', 'Invalid contact order.');
    }

    public function reorderSocialLinks(Request $request): JsonResponse
    {
        return $this->reorderByDisplayOrder($request, SocialLink::class, 'social_links', 'Invalid social link order.');
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
            'image' => [$ignoreId ? 'nullable' : 'nullable', 'image', 'max:5120'],
        ]);
    }

    /**
     * @param  class-string<CarouselSlide|ContactChannel|SocialLink>  $modelClass
     */
    private function reorderByDisplayOrder(Request $request, string $modelClass, string $table, string $errorMessage): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', "exists:{$table},id"],
        ]);

        $ids = collect($validated['order'])->unique()->values();

        if ($ids->count() !== $modelClass::query()->count()) {
            return response()->json(['message' => $errorMessage], 422);
        }

        DB::transaction(function () use ($ids, $modelClass) {
            foreach ($ids as $index => $id) {
                $modelClass::query()->whereKey($id)->update(['display_order' => $index]);
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
