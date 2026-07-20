@extends('layouts.admin')

@section('title', 'Configure access — '.$user->username)

@section('admin-content')
    <a href="{{ route('admin.users.index') }}" class="tich-link">&larr; All users</a>

    <h1 class="tich-h1 tich-mt-4" style="font-size: 2rem;">{{ $user->username }}</h1>
    <p class="tich-text">{{ $user->email }} · {{ ucfirst($user->user_type) }}</p>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="tich-mt-8">
        @csrf
        @method('PUT')

        <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
            <article class="tich-card">
                <h2 class="tich-h3">Role &amp; scope</h2>

                <div class="tich-form-group tich-mt-4">
                    <label class="tich-label">Primary role</label>
                    <select name="role_id" class="tich-input" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $assignedRoleId) == $role->id)>
                                {{ $role->role_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="tich-form-group">
                    <label class="tich-label">Campus scope</label>
                    <select name="campus_id" class="tich-input">
                        <option value="">All campuses</option>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}" @selected(old('campus_id', $assignedCampusId) == $campus->id)>
                                {{ $campus->campus_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="tich-form-group">
                    <label class="tich-label">Department scope</label>
                    <select name="department_id" class="tich-input">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $assignedDepartmentId) == $department->id)>
                                {{ $department->dept_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Dashboard modules</h2>
                <p class="tich-text tich-mb-4">Grant direct access to platform areas. Role permissions also apply.</p>

                <div style="display: grid; gap: 0.75rem;">
                    @foreach ($modulePermissions as $module)
                        <label style="display: flex; gap: 0.5rem; align-items: flex-start;">
                            <input
                                type="checkbox"
                                name="modules[]"
                                value="{{ $module['permission'] }}"
                                @checked(in_array($module['permission'], old('modules', collect($modulePermissions)->where('granted', true)->pluck('permission')->all())))
                            >
                            <span>
                                <strong>{{ $module['label'] }}</strong>
                                @if (!empty($module['coming_soon']))
                                    <span class="tich-caption"> (coming soon)</span>
                                @endif
                                <br>
                                <span class="tich-caption">{{ $module['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </article>
        </div>

        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Effective dashboard (preview)</h2>
            <ul class="tich-mt-4" style="margin: 0; padding-left: 1.25rem;">
                @forelse ($effectiveModules as $module)
                    <li class="tich-text">{{ $module['label'] }}</li>
                @empty
                    <li class="tich-caption">No modules currently visible — assign role permissions or check modules above.</li>
                @endforelse
            </ul>
            <p class="tich-caption tich-mt-4">Preview reflects current access before saving changes.</p>
        </div>

        <button type="submit" class="tich-btn tich-btn-primary tich-mt-6">Save access settings</button>
    </form>
@endsection
