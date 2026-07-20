@extends('layouts.admin')

@section('title', 'Users & access')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Users &amp; access</h1>
    <p class="tich-text tich-mb-8">Assign roles, campus/department scope, and dashboard module permissions.</p>

    <div class="tich-card" style="overflow-x: auto;">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Roles</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->username }}</strong><br>
                            <span class="tich-caption">{{ $user->email }}</span>
                        </td>
                        <td>{{ ucfirst($user->user_type) }}</td>
                        <td>
                            @forelse ($user->roles as $role)
                                {{ $role->role_name }}@if (!$loop->last), @endif
                            @empty
                                <span class="tich-caption">No role</span>
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
