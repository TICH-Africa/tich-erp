@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = ['department' => $department->id];
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <div class="tich-section__intro" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; text-align:left;">
        <div>
            <h1 class="tich-h1" style="font-size: 2rem;">Unit catalog</h1>
            <p class="tich-text">
                @if (! empty($learningDepartment))
                    Units for {{ $learningDepartment->dept_name }}.
                @else
                    Create units for learning departments under {{ $department->dept_name }} and route drafts through registry verification.
                @endif
            </p>
        </div>
        @can('academics.write')
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="unit-create">Add unit</button>
        @endcan
    </div>

    <form method="GET" class="tich-card tich-mt-8" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        @if (empty($learningDepartment))
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Learning department</label>
                <select name="learning_department" class="tich-input">
                    <option value="">All</option>
                    @foreach ($learningDepartments as $learningDepartmentOption)
                        <option value="{{ $learningDepartmentOption->id }}" @selected(($filters['learning_department'] ?? '') == $learningDepartmentOption->id)>{{ $learningDepartmentOption->dept_name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
        @endif
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

    <div class="tich-card tich-table-panel tich-mt-8">
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
                        <td>{{ $unit->department?->dept_name ?? '-' }}</td>
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
                                    <form method="POST" action="{{ route('departments.academics.units.submit', array_merge($hub, ['unit' => $unit->id])) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Submit</button>
                                    </form>
                                @endif
                            @endcan
                            @if ($canApproveRegistry && $unit->status === 'pending_registry')
                                <form method="POST" action="{{ route('departments.academics.units.approve', array_merge($hub, ['unit' => $unit->id])) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-link">Approve</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="tich-table-empty">No units yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('academics.write')
        @include('academics.units.partials.modal', ['modalId' => 'unit-create', 'unit' => null, 'departments' => $learningDepartments, 'hub' => $hub])
        @foreach ($units as $unit)
            @if (in_array($unit->status, ['draft', 'pending_registry']))
                @include('academics.units.partials.modal', ['modalId' => 'unit-edit-'.$unit->id, 'unit' => $unit, 'departments' => $learningDepartments, 'hub' => $hub])
            @endif
        @endforeach
        @include('admin.partials.tich-modal-assets')
    @endcan
@endsection
