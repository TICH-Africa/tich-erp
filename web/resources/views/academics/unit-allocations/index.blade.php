@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <x-page-toolbar title="Unit allocation approvals" meta="Lecturer allocations the department HODs have requested">
        <x-slot:actions>
            <a href="{{ route('departments.academics.dashboard', $hub) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mb-8">
        <form method="GET" action="{{ route('departments.academics.unit-allocations.index', $hub) }}" class="tich-flex-wrap" style="gap:0.75rem; align-items:end;">
            <div class="tich-form-group" style="margin:0;">
                <label for="status" class="tich-label">Status</label>
                <select id="status" name="status" class="tich-select">
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        </form>
        <p class="tich-caption tich-mt-2">
            Approving an allocation puts the unit in front of the lecturer and their students. Rejecting it sends it back to the department.
        </p>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Programme</th>
                        <th>Lecturer</th>
                        <th>Semester</th>
                        <th>Requested</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($allocations as $allocation)
                        <tr>
                            <td>
                                <strong>{{ $allocation->unit?->unit_code ?? '-' }}</strong>
                                <p class="tich-caption">{{ $allocation->unit?->unit_name }}</p>
                            </td>
                            <td>
                                {{ $allocation->program?->program_code ?? '-' }}
                                <p class="tich-caption">{{ $allocation->unit?->department?->dept_name }}</p>
                            </td>
                            <td>{{ $allocation->staff?->fullName() ?? '-' }}</td>
                            <td>
                                {{ $allocation->semester?->semester_label ?? '-' }}
                                <p class="tich-caption">{{ $allocation->campus?->campus_name }} · {{ $allocation->contact_hours_assigned }}h</p>
                            </td>
                            <td>
                                {{ $allocation->requestedBy?->name ?? '-' }}
                                <p class="tich-caption">{{ $allocation->submitted_at?->format('d M Y H:i') }}</p>
                                @if ($allocation->approval_comments)
                                    <p class="tich-caption">{{ $allocation->approval_comments }}</p>
                                @endif
                            </td>
                            <td>
                                @if ($allocation->awaitingApproval())
                                    <div class="tich-flex-wrap" style="gap:0.5rem;">
                                        <form method="POST" action="{{ route('departments.academics.unit-allocations.approve', array_merge($hub, ['unitAllocation' => $allocation->id])) }}">
                                            @csrf
                                            <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('departments.academics.unit-allocations.reject', array_merge($hub, ['unitAllocation' => $allocation->id])) }}" class="tich-flex-wrap" style="gap:0.5rem;">
                                            @csrf
                                            <input type="text" name="comments" class="tich-input" style="min-width:12rem;" placeholder="Reason (optional)">
                                            <button type="submit" class="tich-btn tich-btn-secondary">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="tich-badge">{{ $statusLabels[$allocation->status] ?? $allocation->status }}</span>
                                    @if ($allocation->decidedBy)
                                        <p class="tich-caption">{{ $allocation->decidedBy->name }} · {{ $allocation->approved_at?->format('d M Y') }}</p>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'Nothing waiting', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($allocations, 'hasPages') && $allocations->hasPages())
            <div class="tich-mt-4">{{ $allocations->links() }}</div>
        @endif
    </div>
@endsection
