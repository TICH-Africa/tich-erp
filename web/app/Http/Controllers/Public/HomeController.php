<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected HomepageService $homepageService,
        protected \App\Services\AboutContentService $aboutContent,
    ) {}

    public function index(): View
    {
        $homepage = $this->homepageService->getPayload();

        return view('home', $homepage);
    }

    public function about(): View
    {
        return view('pages.about', [
            'blocks' => $this->aboutContent->activeBlocks(),
        ]);
    }
}
