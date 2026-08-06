<?php

namespace App\Services;

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

        if (! $this->tableExists()) {
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
            'brand_name' => $this->get('site.brand_name', $shortName),
            'brand_tagline' => $this->get('site.brand_tagline', $tagline),
        ];
    }

    public function storePublicUpload(?UploadedFile $file, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        $stored = $file->store(trim($directory, '/'), 'public');

        return 'storage/'.$stored;
    }

    public function deletePublicAsset(?string $path): void
    {
        if (! $path) {
            return;
        }

        $relative = str_starts_with($path, 'storage/') ? substr($path, 8) : ltrim($path, '/');

        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    public function publicAssetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = str_starts_with($path, 'storage/')
            ? substr($path, 8)
            : ltrim($path, '/');

        return Storage::disk('public')->url($relative);
    }

    public function forgetCache(): void
    {
        $this->cache = null;
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('site_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
