@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="Work from home" meta="Independent of leave · Monday to Friday · 1 day per week, carry within the month">
        <x-slot:actions>
            <a href="{{ route('employee.wfh.create') }}" class="tich-btn tich-btn-primary">+ Apply</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('success'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('success') }}</div>
    @endif

    <div class="tich-grid tich-grid--3 tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">This month earned</p>
            <p class="tich-stat__value">{{ $entitlement['earned'] }} / {{ $entitlement['weeks_in_month'] }}</p>
            <p class="tich-caption">Days unlocked through current week</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Used / pending</p>
            <p class="tich-stat__value">{{ $entitlement['used'] }}</p>
            <p class="tich-caption">{{ \Carbon\Carbon::create($entitlement['year'], $entitlement['month'], 1)->format('F Y') }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Available now</p>
            <p class="tich-stat__value">{{ $entitlement['available'] }}</p>
            <p class="tich-caption">Unused days expire at month end</p>
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>WFH date</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td><strong>{{ $item->request_code }}</strong></td>
                            <td>{{ $item->work_date?->format('D, d M Y') }}</td>
                            <td>
                                @if ($item->start_time && $item->end_time)
                                    {{ \Illuminate\Support\Str::of($item->start_time)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($item->end_time)->substr(0, 5) }}
                                    @if ($item->total_hours)
                                        <span class="tich-caption">({{ $item->total_hours }}h)</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td><span class="uf-badge">{{ $item->statusLabel() }}</span></td>
                            <td><a href="{{ route('employee.wfh.show', $item) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-text tich-text--muted">No work from home requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="tich-mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
