@extends('layouts.administration')

@section('title', 'Minutes')

@section('administration-content')
    <x-page-toolbar title="Minutes" meta="Upload and archive meeting minutes documents">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="minutes-create-modal">+ Upload minutes</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Meeting date</th>
                        <th>Time</th>
                        <th>Venue</th>
                        <th>Uploaded by</th>
                        <th>Document</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($minutes as $minute)
                        <tr>
                            <td><strong>{{ $minute->minute_code }}</strong></td>
                            <td>
                                <div>{{ $minute->title }}</div>
                                @if ($minute->notes)
                                    <p class="tich-caption tich-mt-1">{{ \Illuminate\Support\Str::limit($minute->notes, 80) }}</p>
                                @endif
                            </td>
                            <td class="tich-caption">{{ $minute->meeting_date?->format('d M Y') ?? '-' }}</td>
                            <td class="tich-caption">{{ $minute->meetingTimeLabel() ?? '-' }}</td>
                            <td>{{ $minute->venue ?: '-' }}</td>
                            <td class="tich-caption">{{ $minute->uploader?->displayName() ?? '-' }}</td>
                            <td>
                                <a href="{{ route('administration.minutes.view', $minute) }}" class="tich-link" target="_blank" rel="noopener noreferrer">
                                    {{ $minute->original_filename ? \Illuminate\Support\Str::limit($minute->original_filename, 28) : 'Open' }}
                                </a>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('administration.minutes.destroy', $minute) }}" onsubmit="return confirm('Remove these minutes?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-btn tich-btn-ghost tich-btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 8,
                            'title' => 'No meeting minutes uploaded yet',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($minutes instanceof \Illuminate\Contracts\Pagination\Paginator && $minutes->hasPages())
            <div class="tich-mt-4">{{ $minutes->links() }}</div>
        @endif
    </div>

    <div id="minutes-create-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="minutes-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin:0;">Upload meeting minutes</h2>
                <button type="button" class="tich-modal__close" data-close-modal="minutes-create-modal">&times;</button>
            </header>
            <form method="POST" action="{{ route('administration.minutes.store') }}" class="tich-modal__body" enctype="multipart/form-data">
                @csrf
                <div class="tich-form-stack">
                    <div class="tich-form-group">
                        <label class="tich-label" for="minute-title">Title</label>
                        <input id="minute-title" type="text" name="title" class="tich-input" value="{{ old('title') }}" required maxlength="300" placeholder="e.g. Academic Board meeting">
                    </div>
                    <div class="tich-grid tich-grid--2">
                        <div class="tich-form-group">
                            <label class="tich-label" for="minute-date">Meeting date</label>
                            <input id="minute-date" type="date" name="meeting_date" class="tich-input" value="{{ old('meeting_date') }}" required>
                        </div>
                        <div class="tich-form-group">
                            <label class="tich-label" for="minute-time">Time</label>
                            <input id="minute-time" type="time" name="meeting_time" class="tich-input" value="{{ old('meeting_time') }}">
                        </div>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="minute-venue">Venue</label>
                        <input id="minute-venue" type="text" name="venue" class="tich-input" value="{{ old('venue') }}" maxlength="255" placeholder="e.g. Boardroom, Main campus">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="minute-document">Minutes document</label>
                        <input id="minute-document" type="file" name="document" class="tich-input" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf">
                        <p class="tich-caption tich-mt-1">PDF, Word, or image — max 20 MB</p>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label" for="minute-notes">Notes (optional)</label>
                        <textarea id="minute-notes" name="notes" class="tich-input" rows="3" maxlength="3000">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="minutes-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Upload</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = document.getElementById('minutes-create-modal');
                if (modal) {
                    modal.setAttribute('aria-hidden', 'false');
                    modal.classList.add('is-open');
                }
            });
        </script>
    @endif
@endsection
