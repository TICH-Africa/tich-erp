@extends('layouts.admin')

@section('title', 'Departments')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Departments</h1>
    <p class="tich-text tich-mb-8">Academic, administrative, and support departments linked to campuses.</p>

    <div class="tich-grid tich-grid--2" style="align-items: start; gap: 2rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Add department</h2>
            <form method="POST" action="{{ route('admin.departments.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Department code</label>
                    <input type="text" name="dept_code" class="tich-input" value="{{ old('dept_code') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Department name</label>
                    <input type="text" name="dept_name" class="tich-input" value="{{ old('dept_name') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Category</label>
                    <select name="dept_category" class="tich-input" required>
                        @foreach ($deptCategories as $cat)
                            <option value="{{ $cat }}" @selected(old('dept_category') === $cat)>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Campus</label>
                    <select name="campus_id" class="tich-input">
                        <option value="">Institution-wide</option>
                        @foreach ($campuses as $campus)
                            <option value="{{ $campus->id }}" @selected(old('campus_id') == $campus->id)>{{ $campus->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Create department</button>
            </form>
        </article>

        <div class="tich-card" style="overflow-x: auto;">
            <h2 class="tich-h3">Existing departments</h2>
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Category</th><th>Campus</th></tr>
                </thead>
                <tbody>
                    @forelse ($departments as $dept)
                        <tr>
                            <td>{{ $dept->dept_code }}</td>
                            <td>{{ $dept->dept_name }}</td>
                            <td>{{ ucfirst($dept->dept_category) }}</td>
                            <td>{{ $dept->campus?->campus_name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No departments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
