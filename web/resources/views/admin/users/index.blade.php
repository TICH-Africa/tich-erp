@extends('layouts.admin')

@section('title', 'Users & access')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Users &amp; access</h1>
    <p class="tich-text tich-mb-8">Assign roles per department, campus scope, and dashboard module permissions.</p>

    <div class="tich-card" style="overflow-x: auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Roles</th>
                    <th>Departments</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php
                        $departmentIds = $user->roles
                            ->pluck('pivot.department_id')
                            ->filter()
                            ->unique()
                            ->values();
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
                            <a href="{{ route('admin.users.edit', $user) }}" class="tich-link">Configure access</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $users->links() }}</div>
    </div>
@endsection
