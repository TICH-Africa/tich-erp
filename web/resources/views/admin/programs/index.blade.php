@extends('layouts.admin')

@section('title', 'Programmes & courses')

@section('admin-content')
    <h1 class="tich-h1" style="font-size: 2rem;">Programmes &amp; courses</h1>
    <p class="tich-text tich-mb-8">
        Courses and programmes offered under academic departments (children of Academics).
        Active programmes appear on the public <a href="{{ route('programs.index') }}" class="tich-link">Programs &amp; courses</a> page.
    </p>

    @if (session('status'))
        <p class="tich-text tich-mb-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    <div class="tich-grid tich-grid--2" style="align-items: start; gap: 2rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Add programme</h2>
            <form method="POST" action="{{ route('admin.programs.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Programme code</label>
                    <input type="text" name="program_code" class="tich-input" value="{{ old('program_code') }}" required placeholder="e.g. CHP">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Programme name</label>
                    <input type="text" name="program_name" class="tich-input" value="{{ old('program_name') }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Academic department</label>
                    <select name="department_id" class="tich-input" required>
                        <option value="">Select department…</option>
                        @foreach ($learningDepartments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                {{ $department->dept_name }} ({{ $department->dept_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Type</label>
                    <select name="program_type" class="tich-input" required>
                        @foreach ($programTypes as $type)
                            <option value="{{ $type }}" @selected(old('program_type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Regulatory body</label>
                    <input type="text" name="regulatory_body" class="tich-input" value="{{ old('regulatory_body') }}" placeholder="NITA, CDACC, TVET…">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Duration (months)</label>
                    <input type="number" name="duration_months" class="tich-input" value="{{ old('duration_months', 12) }}" min="1">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Status</label>
                    <select name="status" class="tich-input" required>
                        @foreach ($programStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Homepage tagline</label>
                    <textarea name="homepage_tagline" class="tich-input" rows="3">{{ old('homepage_tagline') }}</textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Entry requirements</label>
                    <textarea name="entry_requirements" class="tich-input" rows="2">{{ old('entry_requirements') }}</textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Homepage display order</label>
                    <input type="number" name="homepage_display_order" class="tich-input" value="{{ old('homepage_display_order', 0) }}" min="0">
                </div>
                <label style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="checkbox" name="is_featured_on_homepage" value="1" @checked(old('is_featured_on_homepage'))>
                    <span class="tich-text">Featured on homepage</span>
                </label>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Create programme</button>
            </form>
        </article>

        <div class="tich-card" style="overflow-x: auto;">
            <h2 class="tich-h3">Existing programmes ({{ $programs->count() }})</h2>
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programs as $program)
                        <tr>
                            <td>{{ $program->program_code }}</td>
                            <td>{{ $program->program_name }}</td>
                            <td>{{ $program->department?->dept_name ?? '—' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $program->status)) }}</td>
                            <td>
                                <details>
                                    <summary class="tich-link" style="cursor: pointer;">Edit</summary>
                                    <form method="POST" action="{{ route('admin.programs.update', $program) }}" class="tich-mt-4" style="min-width: 18rem;">
                                        @csrf
                                        @method('PUT')
                                        <div class="tich-form-group">
                                            <label class="tich-label">Code</label>
                                            <input type="text" name="program_code" class="tich-input" value="{{ $program->program_code }}" required>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Name</label>
                                            <input type="text" name="program_name" class="tich-input" value="{{ $program->program_name }}" required>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Department</label>
                                            <select name="department_id" class="tich-input" required>
                                                @foreach ($learningDepartments as $department)
                                                    <option value="{{ $department->id }}" @selected($program->department_id == $department->id)>
                                                        {{ $department->dept_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Type</label>
                                            <select name="program_type" class="tich-input" required>
                                                @foreach ($programTypes as $type)
                                                    <option value="{{ $type }}" @selected($program->program_type === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Regulatory body</label>
                                            <input type="text" name="regulatory_body" class="tich-input" value="{{ $program->regulatory_body }}">
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Duration (months)</label>
                                            <input type="number" name="duration_months" class="tich-input" value="{{ $program->duration_months }}" min="1">
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Status</label>
                                            <select name="status" class="tich-input" required>
                                                @foreach ($programStatuses as $status)
                                                    <option value="{{ $status }}" @selected($program->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Tagline</label>
                                            <textarea name="homepage_tagline" class="tich-input" rows="2">{{ $program->homepage_tagline }}</textarea>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Entry requirements</label>
                                            <textarea name="entry_requirements" class="tich-input" rows="2">{{ $program->entry_requirements }}</textarea>
                                        </div>
                                        <div class="tich-form-group">
                                            <label class="tich-label">Display order</label>
                                            <input type="number" name="homepage_display_order" class="tich-input" value="{{ $program->homepage_display_order ?? 0 }}" min="0">
                                        </div>
                                        <label style="display: flex; gap: 0.5rem; align-items: center;">
                                            <input type="checkbox" name="is_featured_on_homepage" value="1" @checked($program->is_featured_on_homepage)>
                                            <span class="tich-text">Featured</span>
                                        </label>
                                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No programmes yet. Add academic departments under Academics first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="tich-caption tich-mt-6">
        <a href="{{ route('admin.departments.index') }}" class="tich-link">← Departments</a>
    </p>
@endsection
