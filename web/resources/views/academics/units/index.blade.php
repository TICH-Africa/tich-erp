@extends('layouts.academics')

@section('academics-content')
    <div class="tich-section__intro" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; text-align:left;">
        <div>
            <h1 class="tich-h1" style="font-size: 2rem;">Unit catalog</h1>
            <p class="tich-text">Create units, define learning hours, and route drafts through registry verification.</p>
        </div>
        @can('academics.write')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="unit-create">Add unit</button>
        @endcan
    </div>

    <form method="GET" class="tich-card tich-mt-8" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Department</label>
            <select name="department" class="tich-input">
                <option value="">All</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(($filters['department'] ?? '') == $department->id)>{{ $department->dept_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Status</label>
            <select name="status" class="tich-input">
                <option value="">All</option>
                @foreach ($statusLabels as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    <div class="tich-card tich-mt-8" style="overflow-x:auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Unit</th>
                    <th>Department</th>
                    <th>Contact hrs</th>
                    <th>Total learning hrs</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td>{{ $unit->unit_code }}</td>
                        <td>{{ $unit->unit_name }}</td>
                        <td>{{ $unit->department?->dept_name ?? '—' }}</td>
                        <td>{{ $unit->contact_hours }}</td>
                        <td>{{ $unit->total_learning_hours }}</td>
                        <td>{{ $unit->display_priority }}</td>
                        <td>{{ $statusLabels[$unit->status] ?? ucfirst($unit->status) }}</td>
                        <td style="white-space:nowrap;">
                            @can('academics.write')
                                @if (in_array($unit->status, ['draft', 'pending_registry']))
                                    <button type="button" class="tich-link" data-open-modal="unit-edit-{{ $unit->id }}">Edit</button>
                                @endif
                                @if ($unit->status === 'draft')
                                    <form method="POST" action="{{ route('academics.units.submit', $unit) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Submit</button>
                                    </form>
                                @endif
                            @endcan
                            @if ($canApproveRegistry && $unit->status === 'pending_registry')
                                <form method="POST" action="{{ route('academics.units.approve', $unit) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link">Approve</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="padding:2rem;text-align:center;" class="tich-text">No units yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('academics.write')
        @include('academics.units.partials.modal', ['modalId' => 'unit-create', 'unit' => null, 'departments' => $departments])
        @foreach ($units as $unit)
            @if (in_array($unit->status, ['draft', 'pending_registry']))
                @include('academics.units.partials.modal', ['modalId' => 'unit-edit-'.$unit->id, 'unit' => $unit, 'departments' => $departments])
            @endif
        @endforeach
        @include('admin.partials.tich-modal-assets')
    @endcan
@endsection
