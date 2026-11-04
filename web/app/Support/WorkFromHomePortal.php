<?php

namespace App\Support;

use Illuminate\View\View;

class WorkFromHomePortal
{
    public const UNAVAILABLE_MESSAGE = 'This page is unavailable.';

    public static function enabled(): bool
    {
        return (bool) config('tich-wfh.enabled', false);
    }

    public static function ensureEnabled(): void
    {
        abort_unless(self::enabled(), 403, self::UNAVAILABLE_MESSAGE);
    }

    public static function unavailableView(string $layout, string $contentSection, string $featureLabel = 'Work from home'): View
    {
        return view('partials.feature-unavailable', [
            'layout' => $layout,
            'contentSection' => $contentSection,
            'featureLabel' => $featureLabel,
        ]);
    }
}
