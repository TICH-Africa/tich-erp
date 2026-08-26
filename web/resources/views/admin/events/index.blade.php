@extends('layouts.admin')

@section('title', 'Events')

@section('admin-content')
    @php
        $openCreateModal = $errors->any() && old('_method') !== 'PUT';
        $openEditEventId = old('_method') === 'PUT' ? (int) old('edit_event_id') : null;
        $editEvent = $openEditEventId ? $events->firstWhere('id', $openEditEventId) : null;
    @endphp

    <x-page-toolbar title="Events" meta="Public events, conferences, and homepage hero features">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="event-create-modal">
                Add event
            </button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <h2 class="tich-h3">All events ({{ $events->count() }})</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Starts</th>
                    <th>Public</th>
                    <th>Featured</th>
                    <th style="width: 6rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>
                            @if ($event->coverImageUrl())
                                <img src="{{ $event->coverImageUrl() }}" alt="" class="tich-program-admin-thumb">
                            @else
                                <span class="tich-caption">No image</span>
                            @endif
                        </td>
                        <td>{{ $event->title }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</td>
                        <td>{{ $event->start_datetime?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $event->is_public ? 'Yes' : 'No' }}</td>
                        <td>{{ $event->is_featured ? 'Hero' : '—' }}</td>
                        <td style="display:flex;gap:0.35rem;">
                            <button
                                type="button"
                                class="tich-squircle-btn event-edit-trigger"
                                title="Edit event"
                                aria-label="Edit {{ $event->title }}"
                                data-open-modal="event-edit-modal"
                                data-update-url="{{ route('admin.events.update', $event) }}"
                                data-event-id="{{ $event->id }}"
                                data-title="{{ $event->title }}"
                                data-subtitle="{{ $event->subtitle }}"
                                data-event-type="{{ $event->event_type }}"
                                data-description="{{ $event->description }}"
                                data-start-datetime="{{ $event->start_datetime?->format('Y-m-d\\TH:i') }}"
                                data-end-datetime="{{ $event->end_datetime?->format('Y-m-d\\TH:i') }}"
                                data-venue="{{ $event->venue }}"
                                data-registration="{{ $event->registration_url_or_form }}"
                                data-is-public="{{ $event->is_public ? '1' : '0' }}"
                                data-is-featured="{{ $event->is_featured ? '1' : '0' }}"
                                data-cover-image-url="{{ $event->coverImageUrl() ?? '' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                            <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete" aria-label="Delete {{ $event->title }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
                                    </svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No events yet. Add one to populate the public events feed and optional hero slides.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div
        id="event-create-modal"
        class="tich-modal{{ $openCreateModal ? ' is-open' : '' }}"
        aria-hidden="{{ $openCreateModal ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
    >
        <div class="tich-modal__backdrop" data-close-modal="event-create-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header">
                <h2 class="tich-h3" id="event-create-modal-title">Add event</h2>
                <button type="button" class="tich-squircle-btn" data-close-modal="event-create-modal" aria-label="Close">×</button>
            </div>
            <form method="POST" action="{{ route('admin.events.store') }}" class="tich-modal__body" enctype="multipart/form-data">
                @csrf
                @include('admin.partials.event-form-fields', ['eventTypes' => $eventTypes, 'fieldIdPrefix' => 'create_'])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="event-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save event</button>
                </div>
            </form>
        </div>
    </div>

    <div
        id="event-edit-modal"
        class="tich-modal{{ $editEvent ? ' is-open' : '' }}"
        aria-hidden="{{ $editEvent ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
    >
        <div class="tich-modal__backdrop" data-close-modal="event-edit-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header">
                <h2 class="tich-h3">Edit event</h2>
                <button type="button" class="tich-squircle-btn" data-close-modal="event-edit-modal" aria-label="Close">×</button>
            </div>
            <form
                id="event-edit-form"
                method="POST"
                action="{{ $editEvent ? route('admin.events.update', $editEvent) : '#' }}"
                class="tich-modal__body"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_event_id" id="edit_event_id" value="{{ $editEvent?->id }}">
                @include('admin.partials.event-form-fields', [
                    'event' => $editEvent,
                    'eventTypes' => $eventTypes,
                    'fieldIdPrefix' => 'edit_',
                ])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="event-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Update event</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.event-edit-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = document.getElementById('event-edit-form');
                if (!form) return;
                form.action = btn.getAttribute('data-update-url') || '#';
                document.getElementById('edit_event_id').value = btn.getAttribute('data-event-id') || '';
                var map = {
                    title: 'edit_title',
                    subtitle: 'edit_subtitle',
                    'event-type': 'edit_event_type',
                    description: 'edit_description',
                    'start-datetime': 'edit_start_datetime',
                    'end-datetime': 'edit_end_datetime',
                    venue: 'edit_venue',
                    registration: 'edit_registration_url_or_form',
                };
                Object.keys(map).forEach(function (key) {
                    var el = document.getElementById(map[key]);
                    if (el) el.value = btn.getAttribute('data-' + key) || '';
                });
                var pub = document.getElementById('edit_is_public');
                var feat = document.getElementById('edit_is_featured');
                if (pub) pub.checked = btn.getAttribute('data-is-public') === '1';
                if (feat) feat.checked = btn.getAttribute('data-is-featured') === '1';
                var preview = document.getElementById('edit_cover_preview');
                var url = btn.getAttribute('data-cover-image-url') || '';
                if (preview) {
                    if (url) {
                        preview.src = url;
                        preview.hidden = false;
                        preview.style.display = 'block';
                    } else {
                        preview.removeAttribute('src');
                        preview.hidden = true;
                    }
                }
            });
        });
    </script>
    @endpush
@endsection
