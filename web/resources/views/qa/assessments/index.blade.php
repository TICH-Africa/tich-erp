@extends('layouts.qa')

@section('title', 'IQA assessments')

@section('qa-content')
    <x-page-toolbar title="IQA assessments" meta="NATIONAL POLYTECHNIC QUALITY AUDIT TOOL — multiple drafts allowed; published locked">
        <x-slot:actions>
            @if ($canManage)
                <form method="POST" action="{{ route('qa.assessments.store') }}" class="tich-inline-form">
                    @csrf
                    <input type="hidden" name="assessment_year" value="{{ now()->format('Y') }}">
                    <button type="submit" class="tich-btn tich-btn-primary">New assessment</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Published</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assessments as $row)
                        <tr>
                            <td>
                                <a href="{{ route('qa.assessments.show', $row) }}" class="tich-link">{{ $row->title }}</a>
                                <p class="tich-caption">#{{ $row->id }}</p>
                            </td>
                            <td>{{ $row->assessment_year ?: '—' }}</td>
                            <td>
                                <x-status-badge :status="$row->status" />
                            </td>
                            <td>{{ $row->updated_at?->format('d M Y H:i') }}</td>
                            <td>
                                @if ($row->isPublished())
                                    {{ $row->published_at?->format('d M Y') }}
                                    @if ($row->publisher_name)
                                        <p class="tich-caption">{{ $row->publisher_name }}</p>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="tich-table-actions">
                                @if ($row->isDraft() && $canManage)
                                    <a href="{{ route('qa.assessments.edit', ['assessment' => $row, 'section' => $row->current_section ?: 1]) }}" class="tich-btn tich-btn-primary tich-btn--sm">Continue</a>
                                @else
                                    <a href="{{ route('qa.assessments.show', $row) }}" class="tich-btn tich-btn-secondary tich-btn--sm">Open</a>
                                @endif
                                <a href="{{ route('qa.assessments.pdf', $row) }}" class="tich-btn tich-btn-ghost tich-btn--sm">PDF</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 6,
                            'title' => 'No IQA assessments yet',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($assessments->hasPages())
            <div class="tich-mt-4">{{ $assessments->links() }}</div>
        @endif
    </div>
@endsection
