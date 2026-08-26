<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Portal\BlogPost;
use App\Models\Portal\Event;
use App\Models\Portal\ResearchProject;
use App\Services\AboutContentService;
use App\Services\HomepageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected HomepageService $homepageService,
        protected AboutContentService $aboutContent,
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

    public function research(): View
    {
        $projects = collect();

        if (Schema::hasTable('research_projects')) {
            $projects = ResearchProject::query()
                ->orderByDesc('is_featured')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get();
        }

        return view('pages.research', [
            'projects' => $projects,
            'featured' => $this->homepageService->getFeaturedResearch(),
        ]);
    }

    public function events(): View
    {
        $events = $this->homepageService->getPublicEvents();

        return view('pages.events', [
            'events' => $events,
            'usingFallback' => ['events' => $events->isEmpty()],
        ]);
    }

    public function eventShow(Event $event): View|RedirectResponse
    {
        abort_unless($event->is_public, 404);

        if (preg_match('#^events/\d+$#', request()->path())) {
            return redirect()->route('events.show', $event, 301);
        }

        return view('pages.events-show', [
            'event' => $this->homepageService->mapEventForPublic($event),
        ]);
    }

    public function blog(): View
    {
        $posts = $this->homepageService->getPublishedBlogPosts();

        return view('pages.blog.index', [
            'blogPosts' => $posts,
            'usingFallback' => ['blogPosts' => $posts->isEmpty()],
        ]);
    }

    public function blogShow(string $slug): View
    {
        $post = BlogPost::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        if (Schema::hasColumn('blog_posts', 'view_count')) {
            BlogPost::query()->whereKey($post->id)->increment('view_count');
        }

        return view('pages.blog.show', [
            'post' => $this->homepageService->mapBlogPostForPublic($post),
        ]);
    }

    public function support(): View
    {
        return view('pages.support');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }
}
