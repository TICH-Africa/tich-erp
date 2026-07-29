@extends('layouts.admin')

@section('title', 'Users & access')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Users &amp; access</h1>
    <p class="tich-text tich-mb-8">Assign roles and permissions per department, with optional campus scope.</p>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Roles</th>
                    <th>Departments</th>
                    <th>Permissions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php
                        $roleDepartmentIds = $user->roles
                            ->pluck('pivot.department_id')
                            ->filter()
                            ->unique()
                            ->values();

                        $permissionDepartmentIds = $user->permissions
                            ->pluck('pivot.department_id')
                            ->filter()
                            ->unique()
                            ->values();

                        $departmentIds = $roleDepartmentIds->merge($permissionDepartmentIds)->unique()->values();
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $user->username }}</strong><br>
                            <span class="tich-caption">{{ $user->email }}</span>
                        </td>
                        <td>{{ ucfirst($user->user_type) }}</td>
                        <td>
                            @forelse ($user->roles as $role)
                                {{ $role->role_name }}
                                @if ($role->pivot->department_id)
                                    <span class="tich-caption">· {{ $departmentNames[$role->pivot->department_id] ?? 'Dept #'.$role->pivot->department_id }}</span>
                                @else
                                    <span class="tich-caption">· Institution-wide</span>
                                @endif
                                @if (!$loop->last)<br>@endif
                            @empty
                                <span class="tich-caption">No role</span>
                            @endforelse
                        </td>
                        <td>
                            @forelse ($departmentIds as $departmentId)
                                {{ $departmentNames[$departmentId] ?? 'Dept #'.$departmentId }}@if (!$loop->last), @endif
                            @empty
                                <span class="tich-caption">Institution-wide</span>
                            @endforelse
                        </td>
                        <td>
                            @php $shownPermission = false; @endphp
                            @foreach ($user->permissions as $permission)
                                @if ($moduleLabelsBySlug->has($permission->slug))
                                    @php $shownPermission = true; @endphp
                                    {{ $moduleLabelsBySlug[$permission->slug] }}
                                    @if ($permission->pivot->department_id)
                                        <span class="tich-caption">· {{ $departmentNames[$permission->pivot->department_id] ?? 'Dept #'.$permission->pivot->department_id }}</span>
                                    @else
                                        <span class="tich-caption">· Institution-wide</span>
                                    @endif
                                    @if (!$loop->last)<br>@endif
                                @endif
                            @endforeach
                            @unless ($shownPermission)
                                <span class="tich-caption">From roles only</span>
                            @endunless
                        </td>
                        <td>
                            <a href="{{ route('admin.users.edit', $user) }}" class="tich-link">Configure access</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $users->links() }}</div>
    </div>
@endsection
