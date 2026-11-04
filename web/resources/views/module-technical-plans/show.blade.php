@extends($moduleContext['layout'])

@section('title', $department->dept_name.' · '.$plan->title)

@section($moduleContext['content_section'])
    <x-page-toolbar
        :title="$plan->title"
        :meta="'Technical plan · '.($plan->fiscal_year ?? '-')"
    >
        <x-slot:actions>
            <a href="{{ route($indexRoute) }}" class="tich-btn tich-btn-ghost">Back</a>
            <a href="{{ route($printRoute, ['plan' => $plan->id, 'quarter' => 'all']) }}" class="tich-btn tich-btn-secondary">Print</a>
            <a href="{{ route($pdfRoute, ['plan' => $plan->id, 'quarter' => 'all']) }}" class="tich-btn tich-btn-secondary">Download whole year</a>
            @unless ($plan->isBaselineLocked())
                <a href="{{ route($editRoute, $plan->id) }}" class="tich-btn tich-btn-primary">Revise</a>
            @endunless
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <div class="tich-flex-wrap" style="justify-content:space-between;gap:1rem;">
            <div>
                <p class="tich-caption">Status</p>
                <x-status-badge :status="$plan->status" />
            </div>
            <div>
                <p class="tich-caption">Submitted</p>
                <p>{{ $plan->submitted_at?->format('d M Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="tich-caption">Fiscal year</p>
                <p>{{ $plan->fiscal_year ?? '-' }}</p>
            </div>
        </div>
        @if ($plan->summary)
            <div class="tich-mt-4">
                <p class="tich-caption">Summary</p>
                <p class="tich-text" style="white-space:pre-wrap;">{{ $plan->summary }}</p>
            </div>
        @endif
        @if ($plan->me_notes)
            <div class="tich-mt-4">
                <p class="tich-caption">M&amp;E notes</p>
                <pre class="tich-pre" style="white-space:pre-wrap;margin:0;">{{ $plan->me_notes }}</pre>
            </div>
        @endif
    </div>

    @php
        $hasQuarterTags = $plan->outputs->contains(fn ($o) => $o->quarter !== null);
    @endphp

    @foreach ($quarters as $qKey => $qLabel)
        @if (! $hasQuarterTags && (string) $qKey !== '1')
            @continue
        @endif
        @php
            $rows = $hasQuarterTags
                ? $plan->outputs->filter(fn ($o) => (int) $o->quarter === (int) $qKey)
                : $plan->outputs;
            $quarterRow = $hasQuarterTags ? ($plan->quartersByNumber()[(int) $qKey] ?? null) : null;
        @endphp
        <div class="tich-card tich-table-panel tich-mt-6">
            <div class="tich-flex-wrap" style="justify-content:space-between;align-items:center;gap:0.75rem;">
                <h2 class="tich-h3">{{ $hasQuarterTags ? $qLabel : 'Outputs' }}</h2>
                <div class="tich-flex-wrap" style="align-items:center;gap:0.5rem;">
                    @if ($quarterRow)
                        <x-status-badge :status="$quarterRow->status" :label="$quarterRow->statusLabel()" />
                    @endif
                    @if ($hasQuarterTags)
                        <a href="{{ route($pdfRoute, ['plan' => $plan->id, 'quarter' => (int) $qKey]) }}" class="tich-btn tich-btn-secondary">Download Q{{ $qKey }}</a>
                    @endif
                </div>
            </div>
            @if ($quarterRow?->review_notes)
                <p class="tich-caption tich-mt-1">M&amp;E note on this quarter: {{ $quarterRow->review_notes }}</p>
            @endif
            <div class="tich-table-wrap tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Output</th>
                            <th>Activity</th>
                            <th>Costable item</th>
                            <th>Planned</th>
                            <th>Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td style="white-space:pre-wrap;">{{ $row->output }}</td>
                                <td style="white-space:pre-wrap;">{{ $row->activity }}</td>
                                <td class="tich-caption">{{ $row->costable_item ?? '-' }}</td>
                                <td>{{ number_format((float) $row->planned, 2) }}</td>
                                <td class="tich-caption">{{ $row->planned_unit ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="tich-caption">No outputs for this quarter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection
