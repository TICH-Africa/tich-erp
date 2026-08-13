<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Portal\CarouselSlide;
use Illuminate\Support\Facades\Schema;

class ProgramCarouselSyncService
{
    private const PROGRAM_SLIDE_ORDER_OFFSET = 100;

    public function sync(AcademicProgram $program): void
    {
        if (! Schema::hasTable('homepage_carousel_slides')) {
            return;
        }

        $slide = CarouselSlide::query()->where('program_id', $program->id)->first();

        if (! $program->is_featured_on_homepage || $program->status !== 'active') {
            $slide?->delete();

            return;
        }

        $payload = $this->slidePayload($program);

        if ($slide) {
            $slide->update($payload);

            return;
        }

        CarouselSlide::query()->create($payload);
    }

    public function syncAllFeatured(): int
    {
        if (! Schema::hasTable('homepage_carousel_slides') || ! Schema::hasTable('academic_programs')) {
            return 0;
        }

        $count = 0;

        AcademicProgram::query()
            ->where('is_featured_on_homepage', 1)
            ->where('status', 'active')
            ->orderBy('homepage_display_order')
            ->each(function (AcademicProgram $program) use (&$count) {
                $this->sync($program);
                $count++;
            });

        CarouselSlide::query()
            ->whereNotNull('program_id')
            ->whereNotIn('program_id', AcademicProgram::query()
                ->where('is_featured_on_homepage', 1)
                ->where('status', 'active')
                ->pluck('id'))
            ->each(fn (CarouselSlide $slide) => $slide->delete());

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function slidePayload(AcademicProgram $program): array
    {
        $payload = [
            'program_id' => $program->id,
            'title' => $program->program_name,
            'subtitle' => $this->subtitleFor($program),
            'cta_label' => 'Apply now',
            'cta_url' => '/apply?program='.$program->program_code,
            'display_order' => self::PROGRAM_SLIDE_ORDER_OFFSET + (int) $program->homepage_display_order,
            'is_active' => true,
        ];

        if ($program->cover_image_path) {
            $payload['image_path'] = $program->cover_image_path;
        }

        return $payload;
    }

    /**
     * @return object{title: string, subtitle: ?string, image_path: ?string, video_url: ?string, cta_label: ?string, cta_url: ?string, display_order: int}
     */
    public function mapProgramToSlideObject(AcademicProgram $program): object
    {
        $payload = $this->slidePayload($program);

        return (object) [
            'title' => $payload['title'],
            'subtitle' => $payload['subtitle'],
            'image_path' => $program->coverImageUrl(),
            'video_url' => null,
            'cta_label' => $payload['cta_label'],
            'cta_url' => url($payload['cta_url']),
            'view_url' => route('programs.index', ['search' => $program->program_code]),
            'display_order' => $payload['display_order'],
        ];
    }

    private function subtitleFor(AcademicProgram $program): ?string
    {
        $tagline = trim((string) ($program->homepage_tagline ?? ''));

        if ($tagline !== '') {
            return $tagline;
        }

        $requirements = trim((string) ($program->entry_requirements ?? ''));

        return $requirements !== '' ? $requirements : null;
    }
}
