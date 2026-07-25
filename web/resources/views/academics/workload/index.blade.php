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
            <h1 class="tich-h1" style="font-size: 2rem;">Workload allocation</h1>
            <p class="tich-text">Assign lecturers to units and monitor teaching loads for the semester.</p>
        </div>
    </div>

    <form method="GET" class="tich-card tich-mt-8" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end;">
        @if (empty($learningDepartment))
            <div class="tich-form-group" style="margin:0;">
                <label class="tich-label">Learning department</label>
                <select name="learning_department" class="tich-input">
                    @foreach ($learningDepartments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('learning_department') == $dept->id)>{{ $dept->dept_name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="tich-form-group" style="margin:0;">
            <label class="tich-label">Semester</label>
            <select name="semester" class="tich-input">
                <option value="">All semesters</option>
                @foreach ($semesters as $semester)
                    <option value="{{ $semester->id }}" @selected($selectedSemesterId == $semester->id)>
                        {{ $semester->semester_label }} ({{ $semester->academicYear?->year_label }})
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
    </form>

    @if ($workloadSummary->isNotEmpty())
        <div class="tich-grid tich-grid--3 tich-mt-8">
            @foreach ($workloadSummary as $row)
                <article class="tich-card tich-stat">
                    <p class="tich-caption">{{ trim($row->first_name.' '.$row->surname) }}</p>
                    <p class="tich-stat__value">{{ $row->unit_count }} units</p>
                    <p class="tich-text">{{ $row->total_hours ?? 0 }} contact hours</p>
                </article>
            @endforeach
        </div>
    @endif

    @can('academics.write')
        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Assign lecturer to unit</h2>
            <form method="POST" action="{{ route('departments.academics.workload.store', $hub) }}" class="tich-mt-4" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(12rem, 1fr)); gap:1rem; align-items:end;">
                @csrf
                @if (! empty($learningDepartment))
                    <input type="hidden" name="learning_department" value="{{ $learningDepartment->id }}">
                @endif
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label">Unit</label>
                    <select name="unit_id" class="tich-input" required>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->unit_code }} - {{ $unit->unit_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label">Lecturer</label>
                    <select name="staff_id" class="tich-input" required>
                        @foreach ($staffList as $member)
                            <option value="{{ $member->id }}">{{ $member->fullName() }} ({{ $member->employee_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label">Semester</label>
                    <select name="semester_id" class="tich-input" required>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected($selectedSemesterId == $semester->id)>{{ $semester->semester_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label">Campus</label>
                    <select name="campus_id" class="tich-input" required>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}">{{ $campus->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label">Contact hours</label>
                    <input type="number" name="contact_hours_assigned" class="tich-input" value="4" min="0">
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label class="tich-label"><input type="checkbox" name="is_coordinator" value="1"> Unit coordinator</label>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Assign</button>
            </form>
        </article>
    @endcan

    <div class="tich-card tich-mt-8" style="overflow-x:auto; padding:0;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Lecturer</th>
                    <th>Semester</th>
                    <th>Campus</th>
                    <th>Hours</th>
                    <th>Coordinator</th>
                    @can('academics.write')
                        <th></th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @forelse ($allocations as $allocation)
                    <tr>
                        <td>{{ $allocation->unit?->unit_code }} - {{ $allocation->unit?->unit_name }}</td>
                        <td>{{ $allocation->staff?->fullName() }}</td>
                        <td>{{ $allocation->semester?->semester_label }}</td>
                        <td>{{ $allocation->campus?->campus_name }}</td>
                        <td>{{ $allocation->contact_hours_assigned }}</td>
                        <td>{{ $allocation->is_coordinator ? 'Yes' : 'No' }}</td>
                        @can('academics.write')
                            <td>
                                <form method="POST" action="{{ route('departments.academics.workload.destroy', array_merge($hub, ['allocation' => $allocation->id])) }}" onsubmit="return confirm('Remove this allocation?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-link">Remove</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="tich-text">No lecturer allocations for this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
