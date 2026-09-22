@extends('layouts.marketing')

@section('title', 'Marketing Reports')

@section('department-content')
    <x-page-toolbar title="Marketing Reports" meta="Weekly, quarterly, and annual reports">
        <x-slot:actions>
            <a href="{{ route('marketing.reports.create') }}" class="tich-btn tich-btn-primary">Create Report</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-4">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Prepared By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td><a href="{{ route('marketing.reports.show', $report) }}" class="tich-link">{{ $report->title }}</a></td>
                        <td>{{ ucfirst($report->report_type) }}</td>
                        <td>{{ $report->report_date->format('d M Y') }}</td>
                        <td>{{ $report->preparedBy?->fullName() ?? '-' }}</td>
                        <td>{{ ucfirst($report->status) }}</td>
                        <td>
                            <a href="{{ route('marketing.reports.edit', $report) }}" class="tich-squircle-btn" title="Edit">✎</a>
                            @if ($report->status === 'draft')
                                <form method="POST" action="{{ route('marketing.reports.submit', $report) }}" onsubmit="return confirm('Submit for review?');" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-squircle-btn" title="Submit">→</button>
                                </form>
                            @elseif ($report->status === 'submitted')
                                <form method="POST" action="{{ route('marketing.reports.approve', $report) }}" onsubmit="return confirm('Approve this report?');" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-squircle-btn" title="Approve">✓</button>
                                </form>
                            @elseif ($report->status === 'approved')
                                <a href="{{ route('marketing.reports.show', $report) }}" class="tich-squircle-btn" title="Distribute">⇲</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
@endsection
