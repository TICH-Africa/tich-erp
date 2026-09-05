@extends('layouts.ict')

@section('title', 'Events')

@section('ict-content')
    @php
        $openCreateModal = $errors->any() && old('_method') !== 'PUT';
        $openEditEventId = old('_method') === 'PUT' ? (int) old('edit_event_id') : null;
        $editEvent = $openEditEventId ? $events->firstWhere('id', $openEditEventId) : null;
    @endphp

    <x-page-toolbar title="Events" meta="Public events feed and featured homepage hero slides">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="event-create-modal">Add event</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Starts</th>
                    <th>Featured</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>
                            @if ($event->coverImageUrl())
                                <img src="{{ $event->coverImageUrl() }}" alt="" class="tich-program-admin-thumb">
                            @else
                                <span class="tich-caption">-</span>
                            @endif
                        </td>
                        <td>{{ $event->title }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</td>
                        <td>{{ $event->start_datetime?->format('d M Y H:i') }}</td>
                        <td>{{ $event->is_featured ? 'Hero' : '-' }}</td>
                        <td style="display:flex;gap:0.35rem;">
                            <button
                                type="button"
                                class="tich-squircle-btn event-edit-trigger"
                                data-open-modal="event-edit-modal"
                                data-update-url="{{ route('ict.content.events.update', $event) }}"
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
                            >Edit</button>
                            <form method="POST" action="{{ route('ict.content.events.destroy', $event) }}" onsubmit="return confirm('Delete this event?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn">×</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No events yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="event-create-modal" class="tich-modal{{ $openCreateModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateModal ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="event-create-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header"><h2 class="tich-h3">Add event</h2><button type="button" class="tich-squircle-btn" data-close-modal="event-create-modal">×</button></div>
            <form method="POST" action="{{ route('ict.content.events.store') }}" class="tich-modal__body" enctype="multipart/form-data">
                @csrf
                @include('admin.partials.event-form-fields', ['eventTypes' => $eventTypes, 'fieldIdPrefix' => 'create_'])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="event-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="event-edit-modal" class="tich-modal{{ $editEvent ? ' is-open' : '' }}" aria-hidden="{{ $editEvent ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="event-edit-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header"><h2 class="tich-h3">Edit event</h2><button type="button" class="tich-squircle-btn" data-close-modal="event-edit-modal">×</button></div>
            <form id="event-edit-form" method="POST" action="{{ $editEvent ? route('ict.content.events.update', $editEvent) : '#' }}" class="tich-modal__body" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_event_id" id="edit_event_id" value="{{ $editEvent?->id }}">
                @include('admin.partials.event-form-fields', ['event' => $editEvent, 'eventTypes' => $eventTypes, 'fieldIdPrefix' => 'edit_'])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="event-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    @include('admin.partials.tich-modal-assets')
    <x-asset.script path="js/tich-cms-editor.js" :defer="false" />
    <script>
        document.querySelectorAll('.event-edit-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = document.getElementById('event-edit-form');
                form.action = btn.getAttribute('data-update-url');
                document.getElementById('edit_event_id').value = btn.getAttribute('data-event-id') || '';
                var map = {title:'edit_title',subtitle:'edit_subtitle','event-type':'edit_event_type','start-datetime':'edit_start_datetime','end-datetime':'edit_end_datetime',venue:'edit_venue',registration:'edit_registration_url_or_form'};
                Object.keys(map).forEach(function (k) {
                    var el = document.getElementById(map[k]);
                    if (el) el.value = btn.getAttribute('data-' + k) || '';
                });
                if (window.tichCmsEditor) {
                    window.tichCmsEditor.setHtml('edit_description', btn.getAttribute('data-description') || '');
                } else {
                    var desc = document.getElementById('edit_description');
                    if (desc) desc.value = btn.getAttribute('data-description') || '';
                }
                var pub = document.getElementById('edit_is_public');
                var feat = document.getElementById('edit_is_featured');
                if (pub) pub.checked = btn.getAttribute('data-is-public') === '1';
                if (feat) feat.checked = btn.getAttribute('data-is-featured') === '1';
            });
        });
    </script>
    @endpush
@endsection
