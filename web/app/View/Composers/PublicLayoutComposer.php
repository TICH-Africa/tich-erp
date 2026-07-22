<?php

namespace App\View\Composers;

use App\Services\NavigationService;
use Illuminate\View\View;

class PublicLayoutComposer
{
    public function __construct(protected NavigationService $navigationService) {}

    public function compose(View $view): void
    {
        $view->with([
            'headerMenu' => $this->navigationService->getHeaderMenu(),
            'footerPrimaryMenu' => $this->navigationService->getFooterPrimaryMenu(),
            'footerQuickLinks' => $this->navigationService->getFooterQuickLinks(),
            'contactChannels' => $this->navigationService->getContactChannels(),
            'socialLinks' => $this->navigationService->getSocialLinks(),
            'siteMeta' => $this->navigationService->getSiteMeta(),
            'navService' => $this->navigationService,
        ]);
    }
}
