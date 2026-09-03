@extends('layouts.admin')

@section('title', 'Department groups')

@section('admin-content')
    <x-page-toolbar title="Department groups" meta="Top-level groupings for departments" />

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
            <div class="tich-table-panel__head">
                <h2 class="tich-table-panel__title">Existing groups</h2>
                <p class="tich-table-panel__meta">{{ $groups->count() }} total</p>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Departments</th>
                            <th>Status</th>
                            <th class="tich-admin-table__actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr>
                                <td>{{ $group->display_order }}</td>
                                <td><strong>{{ $group->group_code }}</strong></td>
                                <td>{{ $group->group_name }}</td>
                                <td>{{ $group->departments_count }}</td>
                                <td>
                                    @if ($group->is_active)
                                        <span class="tich-badge tich-badge--success">Active</span>
                                    @else
                                        <span class="tich-badge tich-badge--danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="tich-admin-table__actions">
                                    <details>
                                        <summary class="tich-link" style="cursor: pointer;">Edit</summary>
                                        <form method="POST" action="{{ route('admin.department-groups.update', $group) }}" class="tich-mt-4" style="min-width: 16rem; text-align: left;">
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
                            <tr><td colspan="6" class="tich-table-empty">No department groups yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="tich-caption tich-mt-6">
        <a href="{{ route('admin.departments.index') }}" class="tich-link">Manage departments within groups →</a>
    </p>
@endsection
