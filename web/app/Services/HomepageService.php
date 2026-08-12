<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Portal\BlogPost;
use App\Models\Portal\CarouselSlide;
use App\Models\Portal\Event;
use App\Models\Portal\ResearchProject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomepageService
{
    public function __construct(
        protected ProgramCarouselSyncService $programCarousel,
    ) {}

    public function getPayload(): array
    {
        return [
            'carousel' => $this->getCarouselSlides(),
            'programs' => $this->getFeaturedPrograms(),
            'research' => $this->getFeaturedResearch(),
            'events' => $this->getUpcomingEvents(),
            'blogPosts' => $this->getLatestBlogPosts(),
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
                ->where('is_active', 1)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            if ($slides->isNotEmpty()) {
                return $this->mergeFeaturedProgramSlides(
                    $slides->map(fn ($slide) => $this->mapCarouselSlide($slide))
                );
            }
        }

        $this->carouselUsesFallback = true;

        return $this->mergeFeaturedProgramSlides(
            collect(config('tich-homepage.carousel', []))
                ->map(fn ($slide, $index) => (object) array_merge($slide, [
                    'image_path' => $this->mediaUrl($slide['image_path'] ?? null),
                    'cta_url' => isset($slide['cta_url']) ? url($slide['cta_url']) : null,
                    'display_order' => $index + 1,
                ]))
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

        $featuredPrograms = AcademicProgram::query()
            ->where('is_featured_on_homepage', 1)
            ->where('status', 'active')
            ->orderBy('homepage_display_order')
            ->get();

        foreach ($featuredPrograms as $program) {
            if ($linkedProgramIds->contains($program->id)) {
                continue;
            }

            $slides->push($this->programCarousel->mapProgramToSlideObject($program));
        }

        return $slides
            ->sortBy(fn ($slide) => $slide->display_order ?? PHP_INT_MAX)
            ->values();
    }

    private function mapCarouselSlide(CarouselSlide $slide): object
    {
        return (object) [
            'title' => $slide->title,
            'subtitle' => $slide->subtitle,
            'image_path' => $this->mediaUrl($slide->image_path),
            'video_url' => $slide->video_url,
            'cta_label' => $slide->cta_label,
            'cta_url' => $slide->cta_url ? url($slide->cta_url) : null,
            'display_order' => (int) $slide->display_order,
        ];
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
                    'url' => '#research',
                ];
            }
        }

        $this->researchUsesFallback = true;
        $fallback = config('tich-homepage.research');

        return $fallback ? (object) $fallback : null;
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
            ]));
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
                'url' => '#blog',
                'featured_image_path' => $this->mediaUrl($post['featured_image_path'] ?? null),
            ]));
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
            'apply_url' => route('apply.index', ['program' => $program->program_code]),
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

    private function mapEvent(Event $event): object
    {
        return (object) [
            'title' => $event->title,
            'subtitle' => $event->subtitle,
            'event_type' => $event->event_type,
            'start_datetime' => $event->start_datetime,
            'formatted_date' => $event->start_datetime?->format('M j, Y'),
            'venue' => $event->venue,
            'cover_image_path' => $this->mediaUrl($event->cover_image_path),
            'registration_url_or_form' => $event->registration_url_or_form
                ? (str_starts_with($event->registration_url_or_form, 'http')
                    ? $event->registration_url_or_form
                    : url($event->registration_url_or_form))
                : null,
        ];
    }

    private function mapBlogPost(BlogPost $post): object
    {
        return (object) [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'published_at' => $post->published_at,
            'formatted_date' => $post->published_at?->format('M j, Y'),
            'reading_time_minutes' => $post->reading_time_minutes,
            'featured_image_path' => $this->mediaUrl($post->featured_image_path),
            'url' => '#blog',
        ];
    }

    private function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
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
