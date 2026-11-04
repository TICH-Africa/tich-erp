@extends('layouts.print-document')

@php
    $dept = $plan->department;
    $sectionList = collect($sections);
@endphp

@section('document-content')
    <table class="tich-doc-head">
        <tbody>
            <tr>
                <th>DEPARTMENT</th>
                <td>{{ $dept?->dept_name ?: '________________' }}</td>
                <th>DEPT CODE</th>
                <td>{{ $dept?->dept_code ?: '______' }}</td>
            </tr>
            <tr>
                <th>TECHNICAL PLAN</th>
                <td colspan="3">{{ $plan->title }}</td>
            </tr>
            <tr>
                <th>FISCAL YEAR</th>
                <td>{{ $plan->fiscal_year ?: '________' }}</td>
                <th>QUARTERS COVERED</th>
                <td>{{ $quarterScope }}</td>
            </tr>
            <tr>
                <th>STATUS</th>
                <td>{{ \App\Support\StatusTone::label($plan->status) }}</td>
                <th>SUBMITTED</th>
                <td>{{ $plan->submitted_at?->format('d M Y') ?: '____________' }}</td>
            </tr>
            <tr>
                <th>PREPARED BY</th>
                <td>{{ $plan->submitter?->displayName() ?: '_______________________' }}</td>
                <th>BASELINE LOCKED</th>
                <td>{{ $plan->baseline_locked_at?->format('d M Y') ?: 'Not yet locked' }}</td>
            </tr>
            <tr>
                <th>TOTAL PLANNED</th>
                <td class="num">{{ number_format($totalPlanned, 2) }}</td>
                <th>OUTPUTS</th>
                <td class="num">{{ $sectionList->sum(fn ($section) => $section['rows']->count()) }}</td>
            </tr>
            @if ($plan->summary)
                <tr>
                    <th>PLAN SUMMARY</th>
                    <td colspan="3">{{ $plan->summary }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @foreach ($sectionList as $index => $section)
        <table class="tich-doc-table{{ $index === 0 ? ' tich-doc-page-break' : '' }}">
            <thead>
                <tr>
                    <th colspan="6">
                        {{ $section['title'] }}
                        @if ($section['period'] !== '')
                            ({{ $section['period'] }})
                        @endif
                        &ndash; {{ $section['state'] }}
                    </th>
                </tr>
                <tr>
                    <th style="width:2rem;">NO.</th>
                    <th>OUTPUT</th>
                    <th>ACTIVITY</th>
                    <th>COSTABLE ITEM</th>
                    <th style="width:5rem;">PLANNED</th>
                    <th style="width:5rem;">UNIT</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($section['rows'] as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row->output }}</td>
                        <td>{{ $row->activity }}</td>
                        <td>{{ $row->costable_item ?: '-' }}</td>
                        <td class="num">{{ number_format((float) $row->planned, 2) }}</td>
                        <td>{{ $row->planned_unit ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Not filed by the department.</td>
                    </tr>
                @endforelse
                <tr>
                    <th colspan="4">TOTAL PLANNED</th>
                    <th class="num">{{ number_format((float) $section['planned_total'], 2) }}</th>
                    <th></th>
                </tr>
            </tbody>
        </table>

        <table class="tich-doc-head">
            <tbody>
                <tr>
                    <th>M&amp;E DECISION</th>
                    <td>{{ $section['state'] }}</td>
                    <th>APPROVED BY</th>
                    <td>{{ $section['reviewer'] ?: '_______________________' }}</td>
                    <th style="width:4.5rem;">DATE</th>
                    <td style="width:6rem;white-space:nowrap;">{{ $section['reviewed_at'] ?: '____________' }}</td>
                </tr>
                @if ($section['review_notes'] !== '')
                    <tr>
                        <th>M&amp;E NOTES</th>
                        <td colspan="5">{{ $section['review_notes'] }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach
@endsection
