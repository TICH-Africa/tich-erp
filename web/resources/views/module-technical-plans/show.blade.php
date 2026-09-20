@extends($moduleContext['layout'])

@section('title', $department->dept_name.' · '.$plan->title)

@section($moduleContext['content_section'])
    <x-page-toolbar
        :title="$plan->title"
        :meta="'Technical plan · '.($plan->fiscal_year ?? '—')"
    >
        <x-slot:actions>
            <a href="{{ route($indexRoute) }}" class="tich-btn tich-btn-ghost">Back</a>
            @if (in_array($plan->status, ['draft', 'returned'], true))
                <a href="{{ route($editRoute, $plan->id) }}" class="tich-btn tich-btn-primary">Revise</a>
            @endif
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
                <p>{{ $plan->submitted_at?->format('d M Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="tich-caption">Fiscal year</p>
                <p>{{ $plan->fiscal_year ?? '—' }}</p>
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
        @endphp
        <div class="tich-card tich-table-panel tich-mt-6">
            <h2 class="tich-h3">{{ $hasQuarterTags ? $qLabel : 'Outputs' }}</h2>
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
                                <td>{{ $row->output }}</td>
                                <td>{{ $row->activity }}</td>
                                <td class="tich-caption">{{ $row->costable_item ?? '—' }}</td>
                                <td>{{ number_format((float) $row->planned, 2) }}</td>
                                <td class="tich-caption">{{ $row->planned_unit ?? '—' }}</td>
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
