@extends('layouts.qa')

@section('title', 'Capacity building')

@section('qa-content')
    <x-page-toolbar title="Capacity building" meta="Staff training sessions that support quality standards and exam readiness" />

    <div class="qa-capacity-page tich-mt-8">
        <section class="qa-form-section">
            <header class="qa-form-section__head">
                <h2 class="tich-h3">Record a session</h2>
                <p class="tich-caption tich-mt-2">Log a training or briefing that supports quality readiness.</p>
            </header>

            <form method="POST" action="{{ route('qa.capacity.store') }}" class="qa-form-fields qa-form-fields--capacity">
                @csrf
                <div class="tich-form-group qa-form-fields__full">
                    <label class="tich-label" for="title">Title</label>
                    <input id="title" name="title" class="tich-input" required maxlength="300" value="{{ old('title') }}">
                    @error('title')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="tich-form-group qa-form-fields__full">
                    <label class="tich-label" for="description">Description</label>
                    <textarea id="description" name="description" class="tich-input" rows="3" placeholder="Optional notes about the session focus">{{ old('description') }}</textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="scheduled_at">Scheduled</label>
                    <input id="scheduled_at" type="datetime-local" name="scheduled_at" class="tich-input" value="{{ old('scheduled_at') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="status">Status</label>
                    <select id="status" name="status" class="tich-input" required>
                        <option value="scheduled" @selected(old('status', 'scheduled') === 'scheduled')>Scheduled</option>
                        <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                        <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="audience">Audience</label>
                    <input id="audience" name="audience" class="tich-input" maxlength="300" placeholder="e.g. HODs, tutors" value="{{ old('audience') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="location">Location</label>
                    <input id="location" name="location" class="tich-input" maxlength="300" value="{{ old('location') }}">
                </div>
                <div class="qa-form-footer qa-form-fields__full">
                    <button type="submit" class="tich-btn tich-btn-primary">Save session</button>
                </div>
            </form>
        </section>

        <section class="qa-form-section qa-form-section--table">
            <header class="qa-form-section__head">
                <h2 class="tich-h3">Session registry</h2>
                <p class="tich-caption tich-mt-2">Previously logged capacity-building sessions.</p>
            </header>

            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Scheduled</th>
                            <th>Audience</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sessions as $session)
                            <tr>
                                <td>
                                    <strong>{{ $session->title }}</strong>
                                    @if ($session->description)
                                        <p class="tich-caption">{{ \Illuminate\Support\Str::limit($session->description, 120) }}</p>
                                    @endif
                                </td>
                                <td>{{ $session->scheduled_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $session->audience ?: '-' }}</td>
                                <td>{{ $session->location ?: '-' }}</td>
                                <td><x-status-badge :status="$session->status" /></td>
                            </tr>
                        @empty
                            @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No capacity sessions logged', 'icon' => 'inbox'])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($sessions->hasPages())
                <div class="tich-mt-4">{{ $sessions->links() }}</div>
            @endif
        </section>
    </div>
@endsection
