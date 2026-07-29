@extends('layouts.academics')

@section('academics-content')
    @php($hub = ['department' => $department->id])

    <div class="tich-section__intro" style="text-align: left;">
        <h1 class="tich-h1" style="font-size: 2rem;">Learning departments</h1>
        <p class="tich-text">Set curriculum profiles for schools under {{ $department->dept_name }}. Departments are created in platform admin; configure their curriculum track here.</p>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Profile</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $learningDepartment)
                    <tr>
                        <td>{{ $learningDepartment->dept_code }}</td>
                        <td>{{ $learningDepartment->dept_name }}</td>
                        <td>{{ $profiles[$learningDepartment->curriculum_profile ?? 'standard'] ?? ucfirst($learningDepartment->curriculum_profile ?? 'standard') }}</td>
                        <td>
                            @if ($learningDepartment->is_active)
                                <span class="tich-caption">Active</span>
                            @else
                                <span class="tich-caption">Inactive</span>
                            @endif
                        </td>
                        <td style="white-space: nowrap;">
                            <form method="POST" action="{{ route('departments.academics.departments.update-profile', array_merge($hub, ['learningDepartment' => $learningDepartment->id])) }}" style="display:inline-flex; gap:0.5rem; align-items:center;">
                                @csrf
                                @method('PUT')
                                <select name="curriculum_profile" class="tich-input" style="width:auto;">
                                    @foreach ($profiles as $key => $label)
                                        <option value="{{ $key }}" @selected(($learningDepartment->curriculum_profile ?? 'standard') === $key)>{{ $key }}</option>
                                    @endforeach
                                </select>
                                @can('academics.write')
                                    <button type="submit" class="tich-link">Update profile</button>
                                @endcan
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center;" class="tich-text">No learning departments under this academics hub.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
