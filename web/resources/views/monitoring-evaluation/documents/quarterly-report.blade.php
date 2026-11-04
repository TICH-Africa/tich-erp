@extends('layouts.print-document')

@php
    $blocks = collect($reports);
@endphp

@section('document-content')
    <table class="tich-doc-head">
        <tbody>
            <tr>
                <th>DEPARTMENT</th>
                <td>{{ $department?->dept_name ?: '________________' }}</td>
                <th>DEPT CODE</th>
                <td>{{ $department?->dept_code ?: '______' }}</td>
            </tr>
            <tr>
                <th>TECHNICAL PLAN</th>
                <td colspan="3">{{ $plan?->title ?: '-' }}</td>
            </tr>
            <tr>
                <th>FISCAL YEAR</th>
                <td>{{ $plan?->fiscal_year ?: '________' }}</td>
                <th>QUARTERS COVERED</th>
                <td>{{ $quarterScope }}</td>
            </tr>
            <tr>
                <th>REPORT STATUS</th>
                <td>{{ $blocks[0]['status'] }}</td>
                <th>SUBMITTED</th>
                <td>{{ $blocks[0]['report']->submitted_at?->format('d M Y') ?: '____________' }}</td>
            </tr>
            <tr>
                <th>PREPARED BY</th>
                <td>{{ $blocks[0]['report']->submitter?->displayName() ?: '_______________________' }}</td>
                <th>M&amp;E VERIFIED BY</th>
                <td>{{ $blocks[0]['report']->meVerifier?->printedName() ?: '_______________________' }}</td>
            </tr>
            <tr>
                <th>TOTAL PLANNED</th>
                <td class="num">{{ number_format($blocks->sum('planned'), 2) }}</td>
                <th>TOTAL ACHIEVED</th>
                <td class="num">{{ number_format($blocks->sum('achieved'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    @foreach ($blocks as $index => $block)
        @php $report = $block['report']; @endphp
        <table class="tich-doc-table{{ $index === 0 ? ' tich-doc-page-break' : '' }}">
            <thead>
                <tr>
                    <th colspan="6">
                        {{ $block['title'] }}
                        @if ($block['period'] !== '')
                            ({{ $block['period'] }})
                        @endif
                        &ndash; {{ $block['status'] }}
                        · Achievement rate {{ $block['rate'] === null ? 'n/a' : number_format($block['rate'], 1).'%' }}
                    </th>
                </tr>
                <tr>
                    <th>OUTPUT</th>
                    <th>ACTIVITY</th>
                    <th>COSTABLE ITEM</th>
                    <th style="width:5rem;">PLANNED</th>
                    <th style="width:5rem;">ACHIEVED</th>
                    <th style="width:5rem;">DEVIATION</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($block['lines'] as $line)
                    <tr>
                        <td>{{ $line->output }}</td>
                        <td>{{ $line->activity }}</td>
                        <td>{{ $line->costable_item ?: '-' }}</td>
                        <td class="num">{{ number_format((float) $line->planned, 2) }}</td>
                        <td class="num">{{ number_format((float) $line->achieved, 2) }}</td>
                        <td class="num">{{ number_format((float) $line->deviation, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No outputs carried into this quarter.</td>
                    </tr>
                @endforelse
                <tr>
                    <th colspan="3">QUARTER TOTALS</th>
                    <th class="num">{{ number_format($block['planned'], 2) }}</th>
                    <th class="num">{{ number_format($block['achieved'], 2) }}</th>
                    <th class="num">{{ number_format($block['deviation'], 2) }}</th>
                </tr>
            </tbody>
        </table>

        @if ($report->me_notes)
            <table class="tich-doc-head">
                <tbody>
                    <tr>
                        <th>M&amp;E NOTES</th>
                        <td colspan="3">{{ $report->me_notes }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if ($report->ceo_signature)
            <table class="tich-doc-head">
                <tbody>
                    <tr>
                        <th>CEO SIGNATURE</th>
                        <td>{{ $report->ceo_signature }}</td>
                        <th>SIGNED ON</th>
                        <td>{{ $report->ceo_reviewed_at?->format('d M Y') ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    @endforeach
@endsection
