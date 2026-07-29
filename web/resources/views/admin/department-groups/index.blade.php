@extends('layouts.admin')

@section('title', 'Department groups')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Department groups</h1>
    <p class="tich-text tich-mb-8">
        Top-level groupings such as <em>Institutional Development Management</em> and <em>Others</em>.
        Departments are assigned to a group.
    </p>

    @if (session('status'))
        <p class="tich-text tich-mb-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    <div class="tich-grid tich-grid--2" style="align-items: start; gap: 2rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Add department group</h2>
            <form method="POST" action="{{ route('admin.department-groups.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Group code</label>
                    <input type="text" name="group_code" class="tich-input" value="{{ old('group_code') }}" required placeholder="e.g. IDM">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Group name</label>
                    <input type="text" name="group_name" class="tich-input" value="{{ old('group_name') }}" required placeholder="e.g. Institutional Development Management">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Display order</label>
                    <input type="number" name="display_order" class="tich-input" value="{{ old('display_order', 0) }}" min="0">
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Create group</button>
            </form>
        </article>

        <div class="tich-card tich-table-panel">
            <h2 class="tich-h3">Existing groups</h2>
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Departments</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr>
                            <td>{{ $group->display_order }}</td>
                            <td>{{ $group->group_code }}</td>
                            <td>{{ $group->group_name }}</td>
                            <td>{{ $group->departments_count }}</td>
                            <td>{{ $group->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <details>
                                    <summary class="tich-link" style="cursor: pointer;">Edit</summary>
                                    <form method="POST" action="{{ route('admin.department-groups.update', $group) }}" class="tich-mt-4" style="min-width: 16rem;">
                                        @csrf
                                        @method('PUT')
                                        <div class="tich-form-group">
                                            <label class="tich-label">Code</label>
                                            <input type="text" name="group_code" class="tich-input" value="{{ $group->group_code }}" required>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Name</label>
                                            <input type="text" name="group_name" class="tich-input" value="{{ $group->group_name }}" required>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Order</label>
                                            <input type="number" name="display_order" class="tich-input" value="{{ $group->display_order }}" min="0">
                                        </div>
                                        <label style="display: flex; gap: 0.5rem; align-items: center;">
                                            <input type="checkbox" name="is_active" value="1" @checked($group->is_active)>
                                            <span class="tich-text">Active</span>
                                        </label>
                                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No department groups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="tich-caption tich-mt-6">
        <a href="{{ route('admin.departments.index') }}" class="tich-link">Manage departments within groups →</a>
    </p>
@endsection
