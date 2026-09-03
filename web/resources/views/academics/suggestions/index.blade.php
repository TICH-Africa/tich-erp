@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => request()->integer('learning_department') ?: null,
        ]);
    @endphp

    <x-page-toolbar title="Suggestion box" meta="Student suggestions, comments, and complaints from the portal">
        @unless (app(\App\Services\AcademicsAccessService::class)->isSuggestionsOnly(auth()->user()))
            <x-slot:actions>
                <a href="{{ route('departments.academics.dashboard', $hub) }}" class="tich-btn tich-btn-ghost">Back</a>
            </x-slot:actions>
        @endunless
    </x-page-toolbar>

    <div class="tich-stat-row tich-mb-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Open / in review</p>
            <p class="tich-stat__value">{{ $openCount }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Resolved</p>
            <p class="tich-stat__value">{{ $resolvedCount }}</p>
        </div>
    </div>

    <div class="tich-card tich-table-panel tich-mb-8">
        <form method="GET" action="{{ route('departments.academics.suggestions.index', $hub) }}" class="tich-flex-wrap" style="gap: 0.75rem; align-items: end;">
            <div class="tich-form-group" style="margin: 0;">
                <label for="search" class="tich-label">Search</label>
                <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Student, subject, or text" class="tich-input" style="min-width: 14rem;">
            </div>
            <div class="tich-form-group" style="margin: 0;">
                <label for="category" class="tich-label">Category</label>
                <select id="category" name="category" class="tich-select" style="min-width: 10rem;">
                    <option value="">All</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="margin: 0;">
                <label for="status" class="tich-label">Status</label>
                <select id="status" name="status" class="tich-select" style="min-width: 10rem;">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
            @if ($search !== '' || $status !== '' || $category !== '')
                <a href="{{ route('departments.academics.suggestions.index', $hub) }}" class="tich-btn tich-btn-ghost">Clear</a>
            @endif
        </form>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suggestions as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->student?->fullName() ?? '-' }}</strong>
                                <p class="tich-caption">{{ $item->student?->registration_number ?? '-' }}</p>
                            </td>
                            <td class="tich-caption">{{ $item->categoryLabel() }}</td>
                            <td class="tich-caption">{{ $item->subject ?: \Illuminate\Support\Str::limit($item->body, 60) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $item->statusBadge() }}">{{ $item->statusLabel() }}</span>
                            </td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('departments.academics.suggestions.show', array_merge($hub, ['suggestion' => $item->id])) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No submissions yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($suggestions, 'hasPages') && $suggestions->hasPages())
            <div class="tich-mt-4">{{ $suggestions->links() }}</div>
        @endif
    </div>
@endsection
