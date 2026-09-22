@extends('layouts.marketing')

@section('title', 'Marketing Analytics')

@section('department-content')
    <x-page-toolbar title="Marketing Analytics" meta="Overview of all marketing activities and metrics" />

    <div class="tich-grid tich-grid--4 tich-mt-4 tich-mb-4">
        <article class="tich-card tich-p-4">
            <h3 class="tich-h3">{{ $totalLeads }}</h3>
            <p class="tich-caption tich-text-muted">Total Leads</p>
        </article>
        <article class="tich-card tich-p-4">
            <h3 class="tich-h3">{{ $totalActivities }}</h3>
            <p class="tich-caption tich-text-muted">Total Activities</p>
        </article>
        <article class="tich-card tich-p-4">
            <h3 class="tich-h3">{{ $totalReports }}</h3>
            <p class="tich-caption tich-text-muted">Total Reports</p>
        </article>
        <article class="tich-card tich-p-4">
            <h3 class="tich-h3">{{ $totalStudents }}</h3>
            <p class="tich-caption tich-text-muted">Enrolled Students</p>
        </article>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-4">
        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Leads by Stage</h4>
            @if ($leadsByStage->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($leadsByStage as $stage => $count)
                        <li class="tich-flex tich-justify-between">
                            <span class="tich-text">{{ ucfirst($stage) }}</span>
                            <span class="tich-text tich-text-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No leads yet</p>
            @endif
        </section>

        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Leads by Source</h4>
            @if ($leadsBySource->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($leadsBySource as $source => $count)
                        <li class="tich-flex tich-justify-between">
                            <span class="tich-text">{{ ucfirst(str_replace('_', ' ', $source)) }}</span>
                            <span class="tich-text tich-text-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No leads yet</p>
            @endif
        </section>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-4">
        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Lead Activities</h4>
            <ul class="tich-list tich-list--divided">
                <li class="tich-flex tich-justify-between">
                    <span class="tich-text">Total</span>
                    <span class="tich-text">{{ $totalActivities }}</span>
                </li>
                <li class="tich-flex tich-justify-between">
                    <span class="tich-text">Completed</span>
                    <span class="tich-text tich-text-success">{{ $completedActivities }}</span>
                </li>
                <li class="tich-flex tich-justify-between">
                    <span class="tich-text">Pending</span>
                    <span class="tich-text tich-text-warning">{{ $pendingActivities }}</span>
                </li>
                <li class="tich-flex tich-justify-between">
                    <span class="tich-text">Overdue</span>
                    <span class="tich-text tich-text-danger">{{ $overdueActivities }}</span>
                </li>
            </ul>
        </section>

        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Activities by Type</h4>
            @if ($activitiesByType->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($activitiesByType as $type => $count)
                        <li class="tich-flex tich-justify-between">
                            <span class="tich-text">{{ ucfirst($type) }}</span>
                            <span class="tich-text tich-text-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No activities yet</p>
            @endif
        </section>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-4">
        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Reports by Status</h4>
            @if ($reportsByStatus->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($reportsByStatus as $status => $count)
                        <li class="tich-flex tich-justify-between">
                            <span class="tich-text">{{ ucfirst($status) }}</span>
                            <span class="tich-text tich-text-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No reports yet</p>
            @endif
        </section>

        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Reports by Type</h4>
            @if ($reportsByType->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($reportsByType as $type => $count)
                        <li class="tich-flex tich-justify-between">
                            <span class="tich-text">{{ ucfirst($type) }}</span>
                            <span class="tich-text tich-text-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No reports yet</p>
            @endif
        </section>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-4">
        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Students by Status</h4>
            @if ($studentsByStatus->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($studentsByStatus as $status => $count)
                        <li class="tich-flex tich-justify-between">
                            <span class="tich-text">{{ ucfirst($status) }}</span>
                            <span class="tich-text tich-text-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No students</p>
            @endif
        </section>

        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Top 5 Intakes</h4>
            @if ($recentIntakes->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($recentIntakes as $intake => $count)
                        <li class="tich-flex tich-justify-between">
                            <span class="tich-text">{{ $intake }}</span>
                            <span class="tich-text tich-text-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No intake data</p>
            @endif
        </section>
    </div>

    <div class="tich-grid tich-grid--2 tich-mb-4">
        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Website Content</h4>
            <ul class="tich-list tich-list--divided">
                <li class="tich-flex tich-justify-between"><span class="tich-text">About Us blocks</span><span class="tich-text">{{ $aboutCount }}</span></li>
                <li class="tich-flex tich-justify-between"><span class="tich-text">Blog posts</span><span class="tich-text">{{ $blogCount }}</span></li>
                <li class="tich-flex tich-justify-between"><span class="tich-text">Legal pages</span><span class="tich-text">{{ $pageCount }}</span></li>
                <li class="tich-flex tich-justify-between"><span class="tich-text">Events</span><span class="tich-text">{{ $eventCount }}</span></li>
                <li class="tich-flex tich-justify-between"><span class="tich-text">Hero slides</span><span class="tich-text">{{ $heroSlideCount }}</span></li>
            </ul>
        </section>

        <section class="tich-card tich-p-4">
            <h4 class="tich-h4 tich-mb-3">Recent Leads</h4>
            @if ($recentLeads->isNotEmpty())
                <ul class="tich-list tich-list--divided">
                    @foreach ($recentLeads as $lead)
                        <li class="tich-flex tich-justify-between">
                            <a href="{{ route('marketing.leads.edit', $lead) }}" class="tich-link">{{ $lead->name }}</a>
                            <span class="tich-caption tich-badge tich-badge--{{ $lead->stage === 'won' ? 'success' : ($lead->stage === 'lost' ? 'danger' : 'default') }}">{{ ucfirst($lead->stage) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="tich-caption tich-text-muted">No recent leads</p>
            @endif
        </section>
    </div>

    <section class="tich-card tich-p-4">
        <h4 class="tich-h4 tich-mb-3">Recent Reports</h4>
        @if ($recentReports->isNotEmpty())
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Prepared By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentReports as $report)
                        <tr>
                            <td><a href="{{ route('marketing.reports.show', $report) }}" class="tich-link">{{ $report->title }}</a></td>
                            <td>{{ ucfirst($report->report_type) }}</td>
                            <td>{{ $report->report_date->format('d M Y') }}</td>
                            <td>{{ $report->preparedBy?->fullName() ?? '-' }}</td>
                            <td><span class="tich-badge tich-badge--{{ $report->status === 'approved' ? 'success' : ($report->status === 'submitted' ? 'warning' : ($report->status === 'distributed' ? 'info' : 'default')) }}">{{ ucfirst($report->status) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="tich-caption tich-text-muted">No reports yet</p>
        @endif
    </section>
@endsection