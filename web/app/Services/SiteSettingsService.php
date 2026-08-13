<?php

namespace App\Services;

use App\Models\Site\ContactChannel;
use App\Models\Site\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SiteSettingsService
{
    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    public function get(string $key, ?string $default = null): ?string
    {
        $settings = $this->allKeyed();

        return $settings[$key] ?? $default;
    }

    /**
     * @return array<string, string|null>
     */
    public function allKeyed(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! $this->hasTable('site_settings')) {
            return $this->cache = [];
        }

        $this->cache = SiteSetting::query()
            ->where('is_active', 1)
            ->pluck('setting_value', 'setting_key')
            ->all();

        return $this->cache;
    }

    public function set(string $key, ?string $value, ?int $userId = null, array $meta = []): SiteSetting
    {
        $setting = SiteSetting::query()->updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'value_type' => $meta['value_type'] ?? 'string',
                'group_name' => $meta['group_name'] ?? 'general',
                'label' => $meta['label'] ?? null,
                'description' => $meta['description'] ?? null,
                'is_public' => $meta['is_public'] ?? 1,
                'is_active' => 1,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]
        );

        $this->cache = null;

        return $setting;
    }

    /**
     * @return array<string, mixed>
     */
    public function siteMeta(): array
    {
        $defaults = config('tich-navigation.site', []);

        $shortName = $this->get('site.short_name', $defaults['short_name'] ?? 'TICH in Africa');
        $tagline = $this->get('site.tagline', $defaults['tagline'] ?? '');
        $logoPath = $this->get('site.logo_path');

        return [
            'institution_name' => $this->get('site.institution_name', $defaults['institution_name'] ?? $shortName),
            'short_name' => $shortName,
            'tagline' => $tagline,
            'copyright' => $this->get('site.copyright', $defaults['copyright'] ?? $shortName),
            'website' => $this->get('site.website', $defaults['website'] ?? ''),
            'logo_path' => $logoPath,
            'logo_url' => $this->publicAssetUrl($logoPath),
            'favicon_url' => $this->faviconUrl(),
            'favicon_type' => $this->faviconMimeType(),
            'brand_name' => $this->get('site.brand_name', $shortName),
            'brand_tagline' => $this->get('site.brand_tagline', $tagline),
        ];
    }

    /**
     * Branding payload for official print/PDF documents.
     *
     * @return array<string, mixed>
     */
    public function documentBranding(bool $forPdf = false): array
    {
        $meta = $this->siteMeta();
        $defaults = config('tich-navigation.site', []);

        return [
            'name' => $meta['institution_name'],
            'short_name' => $meta['short_name'],
            'tagline' => $meta['tagline'] ?: ($defaults['tagline'] ?? ''),
            'address' => $this->primaryPhysicalAddress(),
            'copyright' => $meta['copyright'],
            'website' => $meta['website'] ?: ($defaults['website'] ?? ''),
            'logo_src' => $this->documentLogoSrc($forPdf),
            'logo_url' => $meta['logo_url'],
            'brand_initial' => strtoupper(substr($meta['brand_name'] ?? $meta['short_name'] ?? 'T', 0, 1)),
        ];
    }

    public function logoAbsolutePath(): ?string
    {
        $path = $this->get('site.logo_path');

        if (! $path) {
            return null;
        }

        $relative = str_starts_with($path, 'storage/')
            ? substr($path, 8)
            : ltrim($path, '/');

        if (! $relative || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        return Storage::disk('public')->path($relative);
    }

    public function faviconUrl(): string
    {
        return $this->publicAssetUrl($this->get('site.logo_path'))
            ?? asset('images/logo.png');
    }

    public function faviconAbsolutePath(): string
    {
        return $this->logoAbsolutePath() ?? public_path('images/logo.png');
    }

    public function faviconMimeType(): string
    {
        $absolute = $this->faviconAbsolutePath();

        if (! is_file($absolute)) {
            return 'image/png';
        }

        return mime_content_type($absolute) ?: 'image/png';
    }

    public function documentLogoSrc(bool $forPdf = false): ?string
    {
        $absolute = $this->logoAbsolutePath();

        if (! $absolute) {
            return null;
        }

        if ($forPdf) {
            $mime = mime_content_type($absolute) ?: 'image/png';
            $encoded = base64_encode((string) file_get_contents($absolute));

            return 'data:'.$mime.';base64,'.$encoded;
        }

        return $this->publicAssetUrl($this->get('site.logo_path'));
    }

    private function primaryPhysicalAddress(): string
    {
        if ($this->hasTable('contact_channels')) {
            $channel = ContactChannel::query()
                ->where('is_active', 1)
                ->where('channel_type', 'physical_address')
                ->orderByDesc('is_primary')
                ->orderBy('display_order')
                ->first();

            if ($channel) {
                return $channel->display_value ?: $channel->value;
            }
        }

        $address = collect(config('tich-navigation.contact', []))
            ->firstWhere('channel_type', 'physical_address');

        return $address['display_value'] ?? $address['value'] ?? 'Kisumu, Kenya';
    }

    public function storePublicUpload(?UploadedFile $file, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        $stored = app(StoredFileService::class)->store($file, $directory, 'public');

        return 'storage/'.$stored;
    }

    public function deletePublicAsset(?string $path): void
    {
        app(StoredFileService::class)->delete($path, 'public');
    }

    public function publicAssetUrl(?string $path): ?string
    {
        return app(StoredFileService::class)->url($path, 'public');
    }

    public function forgetCache(): void
    {
        $this->cache = null;
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
