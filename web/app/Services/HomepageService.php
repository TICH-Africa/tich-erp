<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Portal\BlogPost;
use App\Models\Portal\CarouselSlide;
use App\Models\Portal\Event;
use App\Models\Portal\ResearchProject;
use App\Services\SiteSettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomepageService
{
    public function __construct(
        protected ProgramCarouselSyncService $programCarousel,
        protected EventCarouselSyncService $eventCarousel,
        protected SiteSettingsService $settings,
    ) {}

    public function getPayload(): array
    {
        return [
            'carousel' => $this->getCarouselSlides(),
            'programs' => $this->getFeaturedPrograms(),
            'research' => $this->getFeaturedResearch(),
            'events' => $this->getUpcomingEvents(),
            'blogPosts' => $this->getLatestBlogPosts(),
            'tickerMessage' => $this->settings->get('site.ticker_message', ''),
            'usingFallback' => [
                'carousel' => $this->carouselUsesFallback,
                'programs' => $this->programsUseFallback,
                'research' => $this->researchUsesFallback,
                'events' => $this->eventsUseFallback,
                'blogPosts' => $this->blogUsesFallback,
            ],
        ];
    }

    private bool $carouselUsesFallback = false;
    private bool $programsUseFallback = false;
    private bool $researchUsesFallback = false;
    private bool $eventsUseFallback = false;
    private bool $blogUsesFallback = false;

    public function getCarouselSlides(): Collection
    {
        if ($this->tableExists('homepage_carousel_slides')) {
            $slides = CarouselSlide::query()
                ->with(['program:id,program_code', 'event:id,title,slug'])
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            if ($slides->isNotEmpty()) {
                return $this->mergeFeaturedEventSlides(
                    $this->mergeFeaturedProgramSlides(
                        $slides->map(fn ($slide) => $this->mapCarouselSlide($slide))
                    )
                );
            }
        }

        $this->carouselUsesFallback = true;

        return $this->mergeFeaturedEventSlides(
            $this->mergeFeaturedProgramSlides(
                collect(config('tich-homepage.carousel', []))
                    ->map(fn ($slide, $index) => (object) array_merge($slide, [
                        'image_path' => $this->mediaUrl($slide['image_path'] ?? null),
                        'cta_url' => isset($slide['cta_url']) ? url($slide['cta_url']) : null,
                        'display_order' => $index + 1,
                    ]))
            )
        );
    }

    /**
     * @param  Collection<int, object>  $slides
     * @return Collection<int, object>
     */
    private function mergeFeaturedProgramSlides(Collection $slides): Collection
    {
        if (! $this->tableExists('academic_programs') || ! $this->columnExists('academic_programs', 'is_featured_on_homepage')) {
            return $slides->values();
        }

        $linkedProgramIds = CarouselSlide::query()
            ->whereNotNull('program_id')
            ->pluck('program_id')
            ->map(fn ($id) => (int) $id);

        $existingTitles = $slides
            ->pluck('title')
            ->filter()
            ->map(fn ($title) => strtolower(trim((string) $title)))
            ->values()
            ->all();

        $featuredPrograms = AcademicProgram::query()
            ->where('is_featured_on_homepage', 1)
            ->where('status', 'active')
            ->orderBy('homepage_display_order')
            ->get();

        foreach ($featuredPrograms as $program) {
            if ($linkedProgramIds->contains($program->id)) {
                continue;
            }

            $programTitle = strtolower(trim((string) ($program->program_name ?? '')));
            if ($programTitle !== '' && in_array($programTitle, $existingTitles, true)) {
                continue;
            }

            $slides->push($this->programCarousel->mapProgramToSlideObject($program));
        }

        return $slides
            ->sortBy(fn ($slide) => $slide->display_order ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @param  Collection<int, object>  $slides
     * @return Collection<int, object>
     */
    private function mergeFeaturedEventSlides(Collection $slides): Collection
    {
        if (! $this->tableExists('events') || ! $this->columnExists('events', 'is_featured')) {
            return $slides->values();
        }

        $linkedEventIds = collect();
        if ($this->tableExists('homepage_carousel_slides') && $this->columnExists('homepage_carousel_slides', 'event_id')) {
            $linkedEventIds = CarouselSlide::query()
                ->whereNotNull('event_id')
                ->pluck('event_id')
                ->map(fn ($id) => (int) $id);
        }

        $existingTitles = $slides
            ->pluck('title')
            ->filter()
            ->map(fn ($title) => strtolower(trim((string) $title)))
            ->values()
            ->all();

        $featuredEvents = Event::query()
            ->where('is_featured', 1)
            ->where('is_public', 1)
            ->where(function ($query) {
                $query->whereNull('end_datetime')
                    ->orWhere('end_datetime', '>=', now()->subDay());
            })
            ->orderBy('start_datetime')
            ->get();

        foreach ($featuredEvents as $event) {
            if ($linkedEventIds->contains($event->id)) {
                continue;
            }

            $eventTitle = strtolower(trim((string) ($event->title ?? '')));
            if ($eventTitle !== '' && in_array($eventTitle, $existingTitles, true)) {
                continue;
            }

            $slides->push($this->eventCarousel->mapEventToSlideObject($event));
        }

        return $slides
            ->sortBy(fn ($slide) => $slide->display_order ?? PHP_INT_MAX)
            ->values();
    }

    private function mapCarouselSlide(CarouselSlide $slide): object
    {
        if ($slide->event_id) {
            $eventUrl = $slide->event
                ? route('events.show', ['event' => $slide->event->id])
                : route('events.show', ['event' => $slide->event_id]);

            return (object) [
                'title' => $slide->title,
                'subtitle' => $slide->subtitle,
                'image_path' => $this->mediaUrl($slide->image_path),
                'video_url' => $slide->video_url,
                'cta_label' => 'View event',
                'cta_url' => $eventUrl,
                'view_url' => null,
                'display_order' => (int) $slide->display_order,
            ];
        }

        return (object) [
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'image_path' => $this->mediaUrl($slide->image_path),
            'video_url' => $slide->video_url,
            'cta_label' => $slide->cta_label,
            'cta_url' => $slide->cta_url ? (str_starts_with($slide->cta_url, 'http') ? $slide->cta_url : url($slide->cta_url)) : null,
            'view_url' => $this->programViewUrl($slide->program?->program_code),
            'display_order' => (int) $slide->display_order,
        ];
    }

    private function programViewUrl(?string $programCode): ?string
    {
        if (! $programCode) {
            return null;
        }

        return route('programs.show', $programCode);
    }

    public function getFeaturedPrograms(): Collection
    {
        if ($this->tableExists('academic_programs')) {
            $query = AcademicProgram::query()
                ->where('status', 'active');

            if ($this->columnExists('academic_programs', 'is_featured_on_homepage')) {
                $featured = (clone $query)
                    ->where('is_featured_on_homepage', 1)
                    ->orderBy('homepage_display_order')
                    ->limit(6)
                    ->get();

                if ($featured->isNotEmpty()) {
                    return $featured->map(fn ($program) => $this->mapProgram($program));
                }
            }

            $programs = $query->orderBy('program_name')->limit(6)->get();

            if ($programs->isNotEmpty()) {
                return $programs->map(fn ($program) => $this->mapProgram($program));
            }
        }

        $this->programsUseFallback = true;

        return collect(config('tich-homepage.programs', []))
            ->map(fn ($program) => (object) array_merge($program, [
                'apply_url' => route('apply.index', ['program' => $program['program_code'] ?? '']),
            ]));
    }

    public function getFeaturedResearch(): ?object
    {
        if ($this->tableExists('research_projects')) {
            $project = ResearchProject::query()
                ->where('is_featured', 1)
                ->orderByDesc('created_at')
                ->first()
                ?? ResearchProject::query()->orderByDesc('created_at')->first();

            if ($project) {
                return (object) [
                    'title' => $project->title,
                    'summary' => $project->summary,
                    'status' => $project->status,
                    'cover_image_path' => $this->mediaUrl($project->cover_image_path),
                    'url' => route('research'),
                ];
            }
        }

        $this->researchUsesFallback = true;
        $fallback = config('tich-homepage.research');

        if ($fallback) {
            $fallback = (object) array_merge($fallback, [
                'url' => route('research'),
            ]);
        }

        return $fallback ?: null;
    }

    public function getUpcomingEvents(int $limit = 6): Collection
    {
        if ($this->tableExists('events')) {
            $events = Event::query()
                ->where('is_public', 1)
                ->where('start_datetime', '>=', now()->subDay())
                ->orderBy('start_datetime')
                ->limit($limit)
                ->get();

            if ($events->isNotEmpty()) {
                return $events->map(fn ($event) => $this->mapEvent($event));
            }
        }

        $this->eventsUseFallback = true;

        return collect(config('tich-homepage.events', []))
            ->map(fn ($event) => (object) array_merge($event, [
                'start_datetime' => $event['start_datetime'],
                'formatted_date' => date('M j, Y', strtotime($event['start_datetime'])),
                'registration_url_or_form' => isset($event['registration_url_or_form'])
                    ? url($event['registration_url_or_form'])
                    : null,
                'url' => route('events'),
                'cover_image_path' => $this->mediaUrl($event['cover_image_path'] ?? null),
            ]));
    }

    /**
     * Full public events listing.
     */
    public function getPublicEvents(int $limit = 48): Collection
    {
        if ($this->tableExists('events')) {
            $events = Event::query()
                ->where('is_public', 1)
                ->orderByDesc('start_datetime')
                ->limit($limit)
                ->get();

            if ($events->isNotEmpty()) {
                return $events->map(fn ($event) => $this->mapEvent($event));
            }
        }

        return $this->getUpcomingEvents($limit);
    }

    public function getLatestBlogPosts(int $limit = 3): Collection
    {
        if ($this->tableExists('blog_posts')) {
            $posts = BlogPost::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get();

            if ($posts->isNotEmpty()) {
                return $posts->map(fn ($post) => $this->mapBlogPost($post));
            }
        }

        $this->blogUsesFallback = true;

        return collect(config('tich-homepage.blog_posts', []))
            ->take($limit)
            ->map(fn ($post) => (object) array_merge($post, [
                'formatted_date' => date('M j, Y', strtotime($post['published_at'])),
                'url' => route('blog'),
                'featured_image_path' => $this->mediaUrl($post['featured_image_path'] ?? null),
            ]));
    }

    /**
     * Full public blog listing.
     */
    public function getPublishedBlogPosts(int $limit = 48): Collection
    {
        if ($this->tableExists('blog_posts')) {
            $posts = BlogPost::query()
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get();

            if ($posts->isNotEmpty()) {
                return $posts->map(fn ($post) => $this->mapBlogPost($post));
            }
        }

        return $this->getLatestBlogPosts($limit);
    }

    public function mapBlogPostForPublic(BlogPost $post): object
    {
        return $this->mapBlogPost($post, true);
    }

    private function mapProgram(AcademicProgram $program): object
    {
        $feeDisplay = $this->resolveProgramFee($program->id);

        return (object) [
            'program_code' => $program->program_code,
            'program_name' => $program->program_name,
            'program_type' => $program->program_type,
            'regulatory_body' => $program->regulatory_body,
            'duration_months' => $program->duration_months,
            'homepage_tagline' => $program->homepage_tagline ?? null,
            'entry_requirements' => $program->entry_requirements ?? 'See admissions guide for entry requirements.',
            'fee_display' => $feeDisplay,
            'cover_image_url' => $program->coverImageUrl(),
            'apply_url' => route('apply.index', ['program' => $program->program_code]),
            'url' => route('programs.show', $program->program_code),
        ];
    }

    private function resolveProgramFee(int $programId): string
    {
        if (! $this->tableExists('fee_structures')) {
            return 'Contact admissions for current fee structure';
        }

        $fee = DB::table('fee_structures')
            ->where('program_id', $programId)
            ->where('is_active', 1)
            ->orderByDesc('effective_from')
            ->value('total_semester_fee');

        if ($fee) {
            return 'KES '.number_format((float) $fee, 0).' per semester';
        }

        return 'Contact admissions for current fee structure';
    }

    public function mapEventForPublic(Event $event): object
    {
        return $this->mapEvent($event);
    }

    private function mapEvent(Event $event): object
    {
        return (object) [
            'id' => $event->id,
            'title' => $event->title,
            'subtitle' => $event->subtitle,
            'description' => $event->description,
            'event_type' => $event->event_type,
            'start_datetime' => $event->start_datetime,
            'end_datetime' => $event->end_datetime,
            'formatted_date' => $event->start_datetime?->format('M j, Y'),
            'formatted_time' => $event->start_datetime?->format('g:i A'),
            'formatted_end' => $event->end_datetime?->format('M j, Y · g:i A'),
            'venue' => $event->venue,
            'cover_image_path' => $this->mediaUrl($event->cover_image_path),
            'cover_image_url' => $this->mediaUrl($event->cover_image_path),
            'registration_url_or_form' => $event->registration_url_or_form
                ? (str_starts_with($event->registration_url_or_form, 'http')
                    ? $event->registration_url_or_form
                    : url($event->registration_url_or_form))
                : null,
            'url' => route('events.show', $event),
            'slug' => $event->slug,
        ];
    }

    private function mapBlogPost(BlogPost $post, bool $withBody = false): object
    {
        $payload = [
            'title' => $post->title,
            'subtitle' => $post->subtitle,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'published_at' => $post->published_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'formatted_date' => $post->published_at?->format('M j, Y'),
            'reading_time_minutes' => $post->reading_time_minutes,
            'featured_image_path' => $this->mediaUrl($post->featured_image_path),
            'seo_meta_title' => $post->seo_meta_title,
            'seo_meta_description' => $post->seo_meta_description,
            'url' => route('blog.show', $post->slug),
        ];

        if ($withBody) {
            $payload['body'] = $post->body;
        }

        return (object) $payload;
    }

    private function mediaUrl(?string $path): ?string
    {
        return \App\Support\PublicAsset::media($path);
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}
