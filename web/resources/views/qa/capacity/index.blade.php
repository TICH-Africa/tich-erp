@extends('layouts.qa')

@section('title', 'Capacity building')

@section('qa-content')
    <x-page-toolbar title="Capacity building registry" meta="Staff training sessions that support quality standards and exam readiness" />

    <div class="tich-card tich-mt-8">
        <h2 class="tich-h3">Record a session</h2>
        <form method="POST" action="{{ route('qa.capacity.store') }}" class="tich-form-grid tich-form-grid--2 tich-mt-4">
            @csrf
            <div class="tich-form-group" style="grid-column:1/-1;">
                <label class="tich-label" for="title">Title</label>
                <input id="title" name="title" class="tich-input" required maxlength="300">
            </div>
            <div class="tich-form-group" style="grid-column:1/-1;">
                <label class="tich-label" for="description">Description</label>
                <textarea id="description" name="description" class="tich-input" rows="2"></textarea>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="scheduled_at">Scheduled</label>
                <input id="scheduled_at" type="datetime-local" name="scheduled_at" class="tich-input">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="status">Status</label>
                <select id="status" name="status" class="tich-input" required>
                    <option value="scheduled">Scheduled</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="audience">Audience</label>
                <input id="audience" name="audience" class="tich-input" maxlength="300" placeholder="e.g. HODs, tutors">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="location">Location</label>
                <input id="location" name="location" class="tich-input" maxlength="300">
            </div>
            <div class="tich-form-group" style="grid-column:1/-1;">
                <button type="submit" class="tich-btn tich-btn-primary">Save session</button>
            </div>
        </form>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
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
                        <td><span class="tich-badge">{{ $session->status }}</span></td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No capacity sessions logged', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
        @if ($sessions->hasPages())
            <div class="tich-mt-4">{{ $sessions->links() }}</div>
        @endif
    </div>
@endsection
