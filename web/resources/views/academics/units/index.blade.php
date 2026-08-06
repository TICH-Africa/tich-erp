@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => ($learningDepartment ?? null)?->id ?? request()->integer('learning_department') ?: null,
        ]);
        if (! empty($learningDepartment)) {
            $hub['learning_department'] = $learningDepartment->id;
        }
    @endphp

    @include('academics.partials.learning-department-context')

    <x-page-toolbar
        title="Unit catalog"
        :meta="! empty($learningDepartment) ? 'Units for ' . $learningDepartment->dept_name : 'Units for learning departments under ' . $department->dept_name"
    >
        @can('academics.write')
            <x-slot:actions>
                <button type="button" class="tich-btn tich-btn-primary" data-open-modal="unit-create">Add unit</button>
            </x-slot:actions>
        @endcan
        <x-slot:filters>
            <form method="GET" class="tich-page-toolbar__filters-form">
                @include('partials.search-field', ['placeholder' => 'Unit code or name', 'value' => request('search')])
                @if (empty($learningDepartment))
                    <select name="learning_department" class="tich-input tich-input--compact">
                        <option value="">All</option>
                        @foreach ($learningDepartments as $learningDepartmentOption)
                            <option value="{{ $learningDepartmentOption->id }}" @selected(($filters['learning_department'] ?? '') == $learningDepartmentOption->id)>{{ $learningDepartmentOption->dept_name }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
                @endif
                <select name="status" class="tich-input tich-input--compact">
                    <option value="">All</option>
                    @foreach ($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:filters>
    </x-page-toolbar>

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
