@extends('layouts.ceo')

@section('title', 'M&E reports')

@section('ceo-content')
    <x-page-toolbar title="Monitoring & evaluation reports" meta="Verified quarterly packages for executive review and digital signature" />

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Quarter</th>
                    <th>Achievement</th>
                    <th>Delivered</th>
                    <th>CEO signed</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $report)
                    <tr>
                        <td>{{ $report->department?->dept_name }}</td>
                        <td>{{ $report->quarter?->label() }}</td>
                        <td>{{ $report->achievementRate() !== null ? number_format($report->achievementRate(), 1).'%' : '—' }}</td>
                        <td>{{ $report->ceo_delivered_at?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $report->ceo_reviewed_at?->format('d M Y') ?? 'Pending' }}</td>
                        <td><a href="{{ route('ceo.me.show', $report) }}" class="tich-link">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No M&amp;E reports delivered yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $items->links() }}</div>
    </div>

    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">Department health ratings</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr><th>Department</th><th>QA</th><th>M&amp;E</th><th>Health</th><th>Rating</th></tr>
                </thead>
                <tbody>
                    @forelse ($health as $row)
                        <tr>
                            <td>{{ $row->department?->dept_name }}</td>
                            <td>{{ $row->qa_compliance_avg !== null ? number_format($row->qa_compliance_avg, 1).'%' : '—' }}</td>
                            <td>{{ $row->me_achievement_avg !== null ? number_format($row->me_achievement_avg, 1).'%' : '—' }}</td>
                            <td>{{ $row->health_score !== null ? number_format($row->health_score, 1) : '—' }}</td>
                            <td>{{ $row->health_rating ? ucfirst($row->health_rating) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No health scores yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endsection
