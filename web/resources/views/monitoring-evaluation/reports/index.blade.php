@extends('layouts.monitoring-evaluation')

@section('title', 'Quarterly M&E reports')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="Quarterly reports" meta="Verification gate before CEO delivery">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.reports.index', ['status' => 'submitted']) }}" class="tich-btn tich-btn-secondary">Submitted</a>
            <a href="{{ route('monitoring_evaluation.reports.index', ['status' => 'ceo_delivered']) }}" class="tich-btn tich-btn-ghost">CEO delivered</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Quarter</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $report)
                    <tr>
                        <td>{{ $report->department?->dept_name }}</td>
                        <td>{{ $report->quarter?->label() }}</td>
                        <td>{{ str_replace('_', ' ', $report->status) }}</td>
                        <td>{{ $report->submitted_at?->format('d M Y') ?? '-' }}</td>
                        <td><a href="{{ route('monitoring_evaluation.reports.show', $report) }}" class="tich-link">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No reports yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $items->links() }}</div>
    </div>
@endsection
