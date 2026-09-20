@extends($moduleContext['layout'])

@section('title', $department->dept_name.' · Technical plans')

@section($moduleContext['content_section'])
    <x-page-toolbar
        title="Technical plans"
        meta="Independent departmental plans routed directly to M&E, segmented by quarter"
    >
        <x-slot:actions>
            <a href="{{ route($createRoute) }}" class="tich-btn tich-btn-primary">+ New technical plan</a>
        </x-slot:actions>
    </x-page-toolbar>

    <p class="tich-text tich-mt-4">
        Technical plans are separate from budgeting. Submissions go to Monitoring &amp; Evaluation for review.
    </p>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">{{ $department->dept_name }} plans</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Fiscal year</th>
                        <th>Outputs</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $item)
                        <tr>
                            <td><strong>{{ $item->title }}</strong></td>
                            <td class="tich-caption">{{ $item->fiscal_year ?? '—' }}</td>
                            <td class="tich-caption">{{ $item->outputs_count }}</td>
                            <td>
                                <x-status-badge
                                    :status="$item->status"
                                    :label="match($item->status) {
                                        'me_review' => 'M&E review',
                                        'me_approved' => 'M&E approved',
                                        'baseline_locked' => 'Baseline locked',
                                        'returned' => 'Returned — revise',
                                        default => str_replace('_', ' ', ucfirst($item->status)),
                                    }"
                                />
                            </td>
                            <td class="tich-caption">{{ $item->submitted_at?->format('d M Y') ?? '—' }}</td>
                            <td class="tich-flex-wrap" style="gap:0.5rem;">
                                <a href="{{ route($showRoute, $item->id) }}" class="tich-btn tich-btn-ghost">View</a>
                                @if (in_array($item->status, ['draft', 'returned'], true))
                                    <a href="{{ route($editRoute, $item->id) }}" class="tich-btn tich-btn-primary">Revise</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 6,
                            'title' => 'No technical plans yet',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($plans instanceof \Illuminate\Contracts\Pagination\Paginator && $plans->hasPages())
            <div class="tich-mt-4">{{ $plans->links() }}</div>
        @endif
    </div>
@endsection
