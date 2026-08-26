<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portal\Event;
use App\Services\AuditService;
use App\Services\EventCarouselSyncService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected EventCarouselSyncService $eventCarousel,
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        $events = Event::query()
            ->orderByDesc('is_featured')
            ->orderBy('start_datetime')
            ->get();

        return view('admin.events.index', [
            'events' => $events,
            'eventTypes' => ['conference', 'open_day', 'workshop', 'outreach', 'graduation', 'other'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $coverPath = $request->hasFile('cover_image')
            ? $this->files->replace(null, $request->file('cover_image'), 'events', 'public', null, true)
            : null;

        $event = Event::query()->create([
            ...$validated,
            'slug' => Event::uniqueSlug($validated['title']),
            'cover_image_path' => $coverPath,
            'is_public' => $request->boolean('is_public', true),
            'is_featured' => $request->boolean('is_featured'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'portal.event.created',
            'events',
            $event->id,
            null,
            $event->only(['title', 'event_type', 'is_featured', 'is_public']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        $this->eventCarousel->sync($event->fresh());

        return back()->with('status', 'Event created successfully.');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $this->validated($request, $event);
        $old = $event->only(['title', 'event_type', 'is_featured', 'is_public']);

        $updates = [
            ...$validated,
            'is_public' => $request->boolean('is_public', true),
            'is_featured' => $request->boolean('is_featured'),
            'updated_at' => now(),
        ];

        if (! $event->slug) {
            $updates['slug'] = Event::uniqueSlug($validated['title'], $event->id);
        }

        if ($request->hasFile('cover_image')) {
            $updates['cover_image_path'] = $this->files->replace(
                $event->cover_image_path,
                $request->file('cover_image'),
                'events',
                'public',
                null,
                true,
            );
        }

        $event->update($updates);

        $this->auditService->log(
            'portal.event.updated',
            'events',
            $event->id,
            $old,
            $event->only(['title', 'event_type', 'is_featured', 'is_public']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        $this->eventCarousel->sync($event->fresh());

        return back()->with('status', 'Event updated successfully.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $id = $event->id;
        $title = $event->title;

        if ($event->cover_image_path) {
            $this->files->delete($event->cover_image_path, 'public');
        }

        // Remove linked hero slide first.
        $event->is_featured = false;
        $this->eventCarousel->sync($event);

        $event->delete();

        $this->auditService->log(
            'portal.event.deleted',
            'events',
            $id,
            ['title' => $title],
            null,
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Event deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Event $event = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'event_type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'venue' => ['nullable', 'string', 'max:300'],
            'registration_url_or_form' => ['nullable', 'string', 'max:500'],
            'cover_image' => [$event ? 'nullable' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp'],
            'is_public' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);
    }
}
