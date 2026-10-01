@extends('layouts.ceo')

@section('title', 'Quality reports')

@section('ceo-content')
    <x-page-toolbar title="Quality Level Reports" meta="Published IQA assessments from Quality Assurance" />

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Published assessments</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Published</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assessments as $row)
                        <tr>
                            <td>
                                {{ $row->title }}
                                <p class="tich-caption">#{{ $row->id }}</p>
                            </td>
                            <td>{{ $row->assessment_year ?: '-' }}</td>
                            <td><x-status-badge :status="$row->status" /></td>
                            <td>
                                {{ $row->published_at?->format('d M Y H:i') ?? '-' }}
                                @if ($row->publisher_name)
                                    <p class="tich-caption">{{ $row->publisher_name }}</p>
                                @endif
                            </td>
                            <td class="tich-table-actions">
                                <a href="{{ route('ceo.quality.show', $row) }}" class="tich-btn tich-btn-secondary tich-btn--sm">Open</a>
                                <a href="{{ route('ceo.quality.pdf', $row) }}" class="tich-btn tich-btn-ghost tich-btn--sm">PDF</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 5,
                            'title' => 'No published IQA assessments yet',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($assessments, 'hasPages') && $assessments->hasPages())
            <div class="tich-mt-4">{{ $assessments->links() }}</div>
        @endif
    </div>
@endsection
