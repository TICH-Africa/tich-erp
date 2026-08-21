@extends($moduleContext['layout'])

@section('title', $department->dept_name.' · Budgeting')

@section($moduleContext['content_section'])
    <x-page-toolbar
        title="Budgeting"
        meta="Create department budget requests and send them to Administration for aggregation"
    >
        <x-slot:actions>
            <a href="{{ route($createRoute) }}" class="tich-btn tich-btn-primary">+ New budget request</a>
        </x-slot:actions>
    </x-page-toolbar>

    <p class="tich-text tich-mt-4">
        Submitted requests appear in Administration → <strong>Budget aggregation</strong> for cross-department consolidation and approval routing.
    </p>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">{{ $department->dept_name }} requests</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Items</th>
                        <th>Cycle</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        @php
                            $lineCount = is_array($item->standard_line_items) ? count($item->standard_line_items) : 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $item->request_code }}</strong></td>
                            <td>{{ $item->title }}</td>
                            <td>KES {{ number_format((float) $item->requested_amount, 2) }}</td>
                            <td class="tich-caption">{{ $lineCount }} {{ \Illuminate\Support\Str::plural('line', $lineCount) }}</td>
                            <td class="tich-caption">{{ $item->planningCycle?->cycle_code ?? '—' }}</td>
                            <td><span class="tich-badge">{{ match($item->status) {
                                'submitted' => 'Awaiting Administration',
                                'returned' => 'Returned — revise',
                                'finance_review' => 'Finance review',
                                'executive_review' => 'Executive review',
                                default => str_replace('_', ' ', ucfirst($item->status)),
                            } }}</span></td>
                            <td class="tich-caption">{{ $item->submitted_at?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if ($item->status === 'returned')
                                    <a href="{{ route($editRoute, $item->id) }}" class="tich-btn tich-btn-primary">Revise</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', [
                            'colspan' => 8,
                            'title' => 'No budget requests yet',
                            'icon' => 'inbox',
                        ])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests instanceof \Illuminate\Contracts\Pagination\Paginator && $requests->hasPages())
            <div class="tich-mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
