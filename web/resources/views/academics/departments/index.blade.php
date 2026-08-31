@extends('layouts.academics')

@section('academics-content')
    @php($hub = \App\Support\AcademicsRouteParams::for([
        'learning_department' => ($learningDepartment ?? null)?->id ?? request()->integer('learning_department') ?: null,
    ]))

    <x-page-toolbar title="Learning departments" :meta="'Curriculum profiles for schools under ' . $department->dept_name" />

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Profile</th>
                    <th>Pending applications</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $learningDepartmentRow)
                    <tr>
                        <td>{{ $learningDepartmentRow->dept_code }}</td>
                        <td>
                            <a href="{{ route('departments.academics.programs.index', array_merge($hub, ['learning_department' => $learningDepartmentRow->id])) }}" class="tich-link">
                                <strong>{{ $learningDepartmentRow->dept_name }}</strong>
                            </a>
                        </td>
                        <td>{{ $profiles[$learningDepartmentRow->curriculum_profile ?? 'standard'] ?? ucfirst($learningDepartmentRow->curriculum_profile ?? 'standard') }}</td>
                        <td>
                            @php($pendingCount = $pendingApplicationsByDepartment[$learningDepartmentRow->id] ?? 0)
                            @if ($pendingCount > 0)
                                <a href="{{ route('departments.academics.applications.index', array_merge($hub, ['learning_department' => $learningDepartmentRow->id, 'status' => 'pending'])) }}"
                                   class="tich-notification-badge"
                                   title="Review pending applications"
                                   aria-label="{{ $pendingCount }} pending applications">{{ $pendingCount }}</a>
                            @else
                                <span class="tich-caption">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($learningDepartmentRow->is_active)
                                <span class="tich-caption">Active</span>
                            @else
                                <span class="tich-caption">Inactive</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="{{ route('departments.academics.dashboard', array_merge($hub, ['learning_department' => $learningDepartmentRow->id])) }}" class="tich-link tich-mr-4">Open</a>
                            <form method="POST" action="{{ route('departments.academics.departments.update-profile', array_merge($hub, ['learningDepartment' => $learningDepartmentRow->id])) }}" style="display:inline-flex; gap:0.5rem; align-items:center;">
                                @csrf
                                @method('PUT')
                                <select name="curriculum_profile" class="tich-input" style="width:auto;">
                                    @foreach ($profiles as $key => $label)
                                        <option value="{{ $key }}" @selected(($learningDepartmentRow->curriculum_profile ?? 'standard') === $key)>{{ $key }}</option>
                                    @endforeach
                                </select>
                                @can('academics.write')
                                    <button type="submit" class="tich-link">Update profile</button>
                                @endcan
                            </form>
                        </td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No learning departments under this academics hub', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
