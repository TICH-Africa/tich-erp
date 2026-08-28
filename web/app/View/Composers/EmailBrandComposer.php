<?php

namespace App\View\Composers;

use App\Services\SiteSettingsService;
use Illuminate\View\View;

class EmailBrandComposer
{
    public function __construct(protected SiteSettingsService $settings) {}

    public function compose(View $view): void
    {
        $meta = $this->settings->siteMeta();
        $absolute = $this->settings->logoAbsolutePath();
        $fallback = public_path('images/logo.png');

        $view->with('emailBrand', [
            'institution_name' => $meta['institution_name'] ?? 'Tropical Institute of Community Health and Development in Africa',
            'short_name' => $meta['short_name'] ?? 'TICH in Africa',
            'logo_url' => $meta['logo_url'] ?: asset('images/logo.png'),
            'logo_path' => is_file((string) $absolute) ? $absolute : (is_file($fallback) ? $fallback : null),
        ]);
    }
}
