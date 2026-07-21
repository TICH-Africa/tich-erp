@extends('layouts.admin')

@section('title', 'Configure access — '.$user->username)

@section('admin-content')
    <a href="{{ route('admin.users.index') }}" class="tich-link">&larr; All users</a>

    <h1 class="tich-h1 tich-mt-4" style="font-size: 2rem;">{{ $user->username }}</h1>
    <p class="tich-text">{{ $user->email }} · {{ ucfirst($user->user_type) }}</p>

    @if (session('status'))
        <p class="tich-text tich-mt-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="tich-card tich-mt-4" style="border-color: #c0392b;">
            <ul style="margin: 0; padding-left: 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li class="tich-text">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="tich-mt-8">
        @csrf
        @method('PUT')

        <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
            <article class="tich-card">
                <h2 class="tich-h3">Department assignments</h2>
                <p class="tich-text tich-mb-4">
                    Each row grants a role within a department. Users can hold multiple roles across different departments.
                </p>

                @php
                    $rows = old('assignments', $assignments);
                    if ($rows === []) {
                        $rows = [['role_id' => '', 'department_id' => '', 'campus_id' => '']];
                    }
                    $rows[] = ['role_id' => '', 'department_id' => '', 'campus_id' => ''];
                @endphp

                <div id="assignments-list" style="display: grid; gap: 1rem;">
                    @foreach ($rows as $index => $row)
                        <div class="tich-assignment-row" style="display: grid; gap: 0.75rem; padding: 1rem; border: 1px solid var(--tich-border, #e5e7eb); border-radius: 0.5rem;">
                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">Role</label>
                                <select name="assignments[{{ $index }}][role_id]" class="tich-input">
                                    <option value="">Select role…</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected(old("assignments.{$index}.role_id", $row['role_id'] ?? '') == $role->id)>
                                            {{ $role->role_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">Department</label>
                                <select name="assignments[{{ $index }}][department_id]" class="tich-input">
                                    <option value="">Institution-wide (no department)</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected(old("assignments.{$index}.department_id", $row['department_id'] ?? '') == $department->id)>
                                            {{ $department->dept_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="tich-form-group" style="margin: 0;">
                                <label class="tich-label">Campus scope (optional)</label>
                                <select name="assignments[{{ $index }}][campus_id]" class="tich-input">
                                    <option value="">All campuses</option>
                                    @foreach ($campuses as $campus)
                                        <option value="{{ $campus->id }}" @selected(old("assignments.{$index}.campus_id", $row['campus_id'] ?? '') == $campus->id)>
                                            {{ $campus->campus_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="tich-caption tich-mt-4">Leave the last blank row unused, or fill it to add another assignment on save.</p>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Dashboard modules</h2>
                <p class="tich-text tich-mb-4">Grant direct access to platform areas. Role permissions from all department assignments also apply.</p>

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
