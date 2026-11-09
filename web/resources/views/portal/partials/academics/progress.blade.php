@php
    $summary = $academics['progress_summary'] ?? [];
    $semesters = $academics['semester_performance'] ?? [];
    $periods = $academics['periods'] ?? collect();
    $registrations = $academics['registrations'] ?? collect();
    $currentPeriod = $academics['current_period'] ?? null;
    $currentStatus = $academics['current_period_status'] ?? null;
@endphp

<section class="tich-portal-panel tich-mt-6">
    <div class="tich-portal-panel__head">
        <div>
            <h2 class="tich-h3">Academic progress</h2>
            <p class="tich-caption tich-mt-1">
                Programme completion, semester GPA, and unit status across your registrations.
            </p>
        </div>
    </div>

    <div class="tich-grid tich-grid--3 tich-mt-4">
        <article class="tich-stat">
            <p class="tich-stat__label">Units with grades</p>
            <p class="tich-stat__value">{{ (int) ($summary['units_with_grades'] ?? 0) }}</p>
        </article>
        <article class="tich-stat">
            <p class="tich-stat__label">Registered units</p>
            <p class="tich-stat__value">{{ (int) ($summary['registered_unit_count'] ?? 0) }}</p>
        </article>
        <article class="tich-stat">
            <p class="tich-stat__label">Programme completion</p>
            <p class="tich-stat__value">{{ (int) ($summary['completion_percent'] ?? 0) }}%</p>
        </article>
    </div>

    @php $completionPct = (int) ($summary['completion_percent'] ?? 0); @endphp
    <div class="tich-mt-4" style="height: 0.5rem; background: var(--tich-neutral-border); border-radius: 999px; overflow: hidden;">
        <div style="width: {{ $completionPct }}%; height: 100%; background: var(--tich-green);"></div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-4">
        @if (($summary['cumulative_gpa'] ?? null) !== null)
            <p class="tich-text" style="margin:0;">
                Cumulative GPA: <strong>{{ number_format((float) $summary['cumulative_gpa'], 2) }}</strong>
            </p>
        @endif
        @if (($summary['total_credits_earned'] ?? null) !== null)
            <p class="tich-text" style="margin:0;">
                Credits earned (graded units): <strong>{{ number_format((float) $summary['total_credits_earned'], 1) }}</strong>
            </p>
        @endif
    </div>

    @if ($currentPeriod)
        <p class="tich-caption tich-mt-4">
            Current study period:
            <strong>{{ $currentPeriod->label ?? $currentPeriod->period_label ?? ('Semester '.($currentPeriod->semester ?? '')) }}</strong>
            @if ($currentStatus)
                · {{ ucfirst(str_replace('_', ' ', $currentStatus)) }}
            @endif
        </p>
    @endif
</section>

@if ($semesters !== [])
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Progress by semester</h2>
            <p class="tich-caption tich-mt-1">Registered units, CAT/exam marks where recorded, and semester GPA.</p>
        </div>

        @foreach ($semesters as $block)
            <article class="tich-card tich-mt-4">
                <div class="tich-portal-panel__head" style="margin-bottom:0.75rem;">
                    <div>
                        <h3 class="tich-h4">
                            {{ $block['label'] ?? 'Semester' }}
                            @if (! empty($block['year_label']))
                                <span class="tich-caption">· {{ $block['year_label'] }}</span>
                            @endif
                            @if (! empty($block['is_current']))
                                <span class="tich-portal-badge tich-portal-badge--success" style="margin-left:0.35rem;">Current</span>
                            @endif
                        </h3>
                        <p class="tich-caption tich-mt-1">
                            {{ count($block['units'] ?? []) }} unit(s)
                            · {{ (int) ($block['graded_units'] ?? 0) }} graded
                            @if (($block['gpa'] ?? null) !== null)
                                · Semester GPA <strong>{{ number_format((float) $block['gpa'], 2) }}</strong>
                            @endif
                            @if (($block['credits'] ?? null) !== null && (float) $block['credits'] > 0)
                                · {{ number_format((float) $block['credits'], 1) }} credits
                            @endif
                        </p>
                    </div>
                </div>

                @if (! empty($block['units']))
                    <div class="tich-table-wrap">
                        <table class="tich-admin-table">
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th>CAT</th>
                                    <th>Exam</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($block['units'] as $unit)
                                    <tr>
                                        <td>
                                            {{ $unit['unit_code'] ?? '' }}
                                            @if (! empty($unit['unit_name']))
                                                · {{ $unit['unit_name'] }}
                                            @endif
                                        </td>
                                        <td>{{ $unit['cat'] !== null ? number_format((float) $unit['cat'], 1) : '-' }}</td>
                                        <td>{{ $unit['exam'] !== null ? number_format((float) $unit['exam'], 1) : '-' }}</td>
                                        <td>{{ $unit['total'] !== null ? number_format((float) $unit['total'], 1) : '-' }}</td>
                                        <td>{{ $unit['grade'] ?? '-' }}</td>
                                        <td class="tich-caption">{{ $unit['status'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="tich-caption">No units registered for this semester.</p>
                @endif
            </article>
        @endforeach
    </section>
@else
    @include('partials.states.empty', [
        'title' => 'No semester progress yet',
        'description' => 'Register for units each semester. Grades and GPA will appear here as marks are recorded.',
        'icon' => 'inbox',
    ])
@endif

@if ($periods->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Curriculum periods</h2>
        </div>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Starts</th>
                        <th>Ends</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periods as $period)
                        <tr>
                            <td>{{ $period->label ?? $period->period_label ?? ('Semester '.($period->semester ?? '')) }}</td>
                            <td>{{ optional($period->starts_at)->format('d M Y') ?? '-' }}</td>
                            <td>{{ optional($period->ends_at)->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($registrations->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Semester registrations</h2>
        </div>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registrations as $reg)
                        <tr>
                            <td>{{ $reg->semester_label ?? '-' }}</td>
                            <td>{{ ucfirst($reg->status ?? 'registered') }}</td>
                            <td>{{ optional($reg->registration_date)->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
