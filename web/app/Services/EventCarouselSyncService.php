<?php

namespace App\Services;

use App\Models\Portal\CarouselSlide;
use App\Models\Portal\Event;
use Illuminate\Support\Facades\Schema;

class EventCarouselSyncService
{
    private const EVENT_SLIDE_ORDER_OFFSET = 200;

    public function sync(Event $event): void
    {
        if (! Schema::hasTable('homepage_carousel_slides') || ! Schema::hasColumn('homepage_carousel_slides', 'event_id')) {
            return;
        }

        $slide = CarouselSlide::query()->where('event_id', $event->id)->first();

        if (! $event->is_featured || ! $event->is_public) {
            $slide?->delete();

            return;
        }

        $payload = $this->slidePayload($event);

        if ($slide) {
            $slide->update($payload);

            return;
        }

        CarouselSlide::query()->create($payload);
    }

    public function syncAllFeatured(): int
    {
        if (! Schema::hasTable('homepage_carousel_slides')
            || ! Schema::hasTable('events')
            || ! Schema::hasColumn('homepage_carousel_slides', 'event_id')) {
            return 0;
        }

        $count = 0;

        Event::query()
            ->where('is_featured', 1)
            ->where('is_public', 1)
            ->orderBy('start_datetime')
            ->each(function (Event $event) use (&$count) {
                $this->sync($event);
                $count++;
            });

        CarouselSlide::query()
            ->whereNotNull('event_id')
            ->whereNotIn('event_id', Event::query()
                ->where('is_featured', 1)
                ->where('is_public', 1)
                ->pluck('id'))
            ->each(fn (CarouselSlide $slide) => $slide->delete());

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    public function slidePayload(Event $event): array
    {
        $subtitle = trim((string) ($event->subtitle ?: ''));
        if ($subtitle === '' && $event->start_datetime) {
            $subtitle = $event->start_datetime->format('D, j M Y · g:i A');
            if ($event->venue) {
                $subtitle .= ' · '.$event->venue;
            }
        }

        $payload = [
            'event_id' => $event->id,
            'program_id' => null,
            'title' => $event->title,
            'subtitle' => $subtitle !== '' ? $subtitle : null,
            'cta_label' => 'View event',
            'cta_url' => '/events/'.($event->slug ?: $event->id),
            'display_order' => self::EVENT_SLIDE_ORDER_OFFSET + (int) $event->id,
            'is_active' => true,
        ];

        if ($event->cover_image_path) {
            $payload['image_path'] = $event->cover_image_path;
        }

        return $payload;
    }

    /**
     * @return object{title: string, subtitle: ?string, image_path: ?string, video_url: ?string, cta_label: ?string, cta_url: ?string, view_url: ?string, display_order: int}
     */
    public function mapEventToSlideObject(Event $event): object
    {
        $payload = $this->slidePayload($event);

        return (object) [
            'title' => $payload['title'],
            'subtitle' => $payload['subtitle'],
            'image_path' => $event->coverImageUrl(),
            'video_url' => null,
            'cta_label' => 'View event',
            'cta_url' => $event->slug ? route('events.show', $event) : route('events'),
            'view_url' => null,
            'display_order' => $payload['display_order'],
        ];
    }
}
