@extends('layouts.marketing')

@section('title', 'Report: ' . $report->title)

@section('department-content')
    <x-page-toolbar title="Report Details" meta="{{ ucfirst($report->report_type) }} — {{ $report->report_date->format('d M Y') }}" />

    <div class="tich-card tich-mt-4 tich-mb-4">
        <div class="tich-form-grid tich-form-grid--2">
            <div class="tich-form-group">
                <span class="tich-caption">Title</span>
                <p class="tich-text">{{ $report->title }}</p>
            </div>
            <div class="tich-form-group">
                <span class="tich-caption">Type</span>
                <p class="tich-text">{{ ucfirst($report->report_type) }}</p>
            </div>
            <div class="tich-form-group">
                <span class="tich-caption">Date</span>
                <p class="tich-text">{{ $report->report_date->format('d M Y') }}</p>
            </div>
            <div class="tich-form-group">
                <span class="tich-caption">Status</span>
                <p class="tich-text">{{ ucfirst($report->status) }}</p>
            </div>
            <div class="tich-form-group">
                <span class="tich-caption">Prepared By</span>
                <p class="tich-text">{{ $report->preparedBy?->fullName() ?? '-' }}</p>
            </div>
            <div class="tich-form-group">
                <span class="tich-caption">Approved By</span>
                <p class="tich-text">{{ $report->approvedBy?->fullName() ?? '-' }}</p>
            </div>
            @if ($report->distribution_list)
                <div class="tich-form-group" style="grid-column: 1 / -1;">
                    <span class="tich-caption">Distribution List</span>
                    <p class="tich-text">{{ $report->distribution_list }}</p>
                </div>
            @endif
        </div>
    </div>

    @if ($report->summary)
        <div class="tich-card tich-mt-4 tich-mb-4">
            <span class="tich-caption">Summary</span>
            <p class="tich-text tich-mt-2">{{ $report->summary }}</p>
        </div>
    @endif

    @if ($report->commentary)
        <div class="tich-card tich-mt-4 tich-mb-4">
            <span class="tich-caption">Commentary</span>
            <p class="tich-text tich-mt-2">{{ $report->commentary }}</p>
        </div>
    @endif

    @if ($report->anomalies)
        <div class="tich-card tich-mt-4 tich-mb-4">
            <span class="tich-caption">Anomalies</span>
            <p class="tich-text tich-mt-2">{{ $report->anomalies }}</p>
        </div>
    @endif

    @if ($report->attachments->isNotEmpty())
        <div class="tich-card tich-mt-4 tich-mb-4">
            <span class="tich-caption">Attachments</span>
            <div class="tich-mt-2">
                @foreach ($report->attachments as $attachment)
                    <div class="tich-flex tich-items-center tich-gap-3 tich-p-3 tich-border tich-rounded tich-mb-2">
                        <span class="tich-text">{{ $attachment->original_name }}</span>
                        <span class="tich-caption tich-text-muted">({{ number_format($attachment->size / 1024, 1) }} KB)</span>
                        <a href="{{ $attachment->url() }}" target="_blank" class="tich-btn tich-btn-secondary tich-btn-sm">Download</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="tich-card tich-mt-4 tich-mb-4 tich-mb-8">
        <div class="tich-mb-3">
            <span class="tich-caption">Workflow</span>
        </div>
        @if ($report->status === 'draft')
            <form method="POST" action="{{ route('marketing.reports.submit', $report) }}" onsubmit="return confirm('Submit for review?');" style="display:inline;">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Submit for Review</button>
            </form>
        @elseif ($report->status === 'submitted')
            <form method="POST" action="{{ route('marketing.reports.approve', $report) }}" onsubmit="return confirm('Approve this report?');" style="display:inline;">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
            </form>
        @elseif ($report->status === 'approved')
            <form method="POST" action="{{ route('marketing.reports.distribute', $report) }}" onsubmit="return confirm('Distribute this report?');">
                @csrf
                <div class="tich-form-group tich-mb-3" style="max-width: 400px;">
                    <label class="tich-label" for="distribution_list">Distribution List</label>
                    <input id="distribution_list" type="text" name="distribution_list" class="tich-input" placeholder="Comma-separated email addresses or names" required>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Distribute</button>
            </form>
        @endif
        <a href="{{ route('marketing.reports.index') }}" class="tich-btn tich-btn-secondary">Back to Reports</a>
    </div>
@endsection
