@extends('layouts.admin')

@section('title', 'Programmes & courses')

@section('admin-content')
    @php
        $openCreateModal = $errors->any() && old('_method') !== 'PUT';
        $openEditProgramId = old('_method') === 'PUT' ? (int) old('edit_program_id') : null;
        $editProgram = $openEditProgramId ? $programs->firstWhere('id', $openEditProgramId) : null;
    @endphp

    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: start; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1 class="tich-h1" style="font-size: 2rem;">Programmes &amp; courses</h1>
            <p class="tich-text tich-mt-2" style="margin-bottom: 0;">
                Courses and programmes offered under academic departments (children of Academics).
                Active programmes appear on the public <a href="{{ route('programs.index') }}" class="tich-link">Programs &amp; courses</a> page.
            </p>
        </div>
        <button type="button" class="tich-btn tich-btn-primary" data-open-modal="program-create-modal">
            Add programme
        </button>
    </div>

    @if (session('status'))
        <p class="tich-text tich-mb-4" style="color: var(--tich-green);">{{ session('status') }}</p>
    @endif

    <div class="tich-card" style="overflow-x: auto;">
        <h2 class="tich-h3">All programmes ({{ $programs->count() }})</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th style="width: 4rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td>{{ $program->program_code }}</td>
                        <td>{{ $program->program_name }}</td>
                        <td>{{ $program->department?->dept_name ?? '—' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $program->program_type)) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $program->status)) }}</td>
                        <td>
                            <button
                                type="button"
                                class="tich-squircle-btn program-edit-trigger"
                                title="Edit programme"
                                aria-label="Edit {{ $program->program_name }}"
                                data-open-modal="program-edit-modal"
                                data-update-url="{{ route('admin.programs.update', $program) }}"
                                data-program-id="{{ $program->id }}"
                                data-program-code="{{ $program->program_code }}"
                                data-program-name="{{ $program->program_name }}"
                                data-department-id="{{ $program->department_id }}"
                                data-program-type="{{ $program->program_type }}"
                                data-regulatory-body="{{ $program->regulatory_body }}"
                                data-duration-months="{{ $program->duration_months }}"
                                data-status="{{ $program->status }}"
                                data-homepage-tagline="{{ $program->homepage_tagline }}"
                                data-entry-requirements="{{ $program->entry_requirements }}"
                                data-homepage-display-order="{{ $program->homepage_display_order ?? 0 }}"
                                data-is-featured="{{ $program->is_featured_on_homepage ? '1' : '0' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No programmes yet. Add academic departments under Academics first.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="tich-caption tich-mt-6">
        <a href="{{ route('admin.departments.index') }}" class="tich-link">← Departments</a>
    </p>

    {{-- Create modal --}}
    <div
        id="program-create-modal"
        class="tich-modal{{ $openCreateModal ? ' is-open' : '' }}"
        aria-hidden="{{ $openCreateModal ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="program-create-modal-title"
    >
        <div class="tich-modal__backdrop" data-close-modal="program-create-modal"></div>
        <div class="tich-modal__dialog tich-modal__dialog--wide">
            <header class="tich-modal__header">
                <h2 id="program-create-modal-title" class="tich-h3" style="margin: 0;">Add programme</h2>
                <button type="button" class="tich-modal__close" data-close-modal="program-create-modal" aria-label="Close">&times;</button>
            </header>
            <form method="POST" action="{{ route('admin.programs.store') }}" class="tich-modal__body">
                @csrf
                @if ($errors->any() && old('_method') !== 'PUT')
                    <div class="tich-modal__errors">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li class="tich-text">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('admin.partials.program-form-fields', [
                    'learningDepartments' => $learningDepartments,
                    'programTypes' => $programTypes,
                    'programStatuses' => $programStatuses,
                ])
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="program-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Create programme</button>
                </footer>
            </form>
        </div>
    </div>

    {{-- Edit modal --}}
    <div
        id="program-edit-modal"
        class="tich-modal{{ $openEditProgramId ? ' is-open' : '' }}"
        aria-hidden="{{ $openEditProgramId ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="program-edit-modal-title"
    >
        <div class="tich-modal__backdrop" data-close-modal="program-edit-modal"></div>
        <div class="tich-modal__dialog tich-modal__dialog--wide">
            <header class="tich-modal__header">
                <h2 id="program-edit-modal-title" class="tich-h3" style="margin: 0;">Edit programme</h2>
                <button type="button" class="tich-modal__close" data-close-modal="program-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form
                id="program-edit-form"
                method="POST"
                action="{{ $editProgram ? route('admin.programs.update', $editProgram) : '#' }}"
                class="tich-modal__body"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_program_id" id="program-edit-id" value="{{ old('edit_program_id') }}">
                @if ($errors->any() && old('_method') === 'PUT')
                    <div class="tich-modal__errors">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li class="tich-text">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('admin.partials.program-form-fields', [
                    'learningDepartments' => $learningDepartments,
                    'programTypes' => $programTypes,
                    'programStatuses' => $programStatuses,
                    'program' => $editProgram,
                    'fieldIdPrefix' => 'program-edit-',
                ])
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="program-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')

    <script>
    (function () {
        var form = document.getElementById('program-edit-form');
        if (!form) {
            return;
        }

        function setFieldValue(id, value) {
            var field = document.getElementById(id);
            if (!field) {
                return;
            }

            if (field.type === 'checkbox') {
                field.checked = value === '1' || value === true || value === 'true';
                return;
            }

            field.value = value ?? '';
        }

        function fillEditForm(trigger) {
            form.action = trigger.getAttribute('data-update-url') || '#';
            setFieldValue('program-edit-id', trigger.getAttribute('data-program-id'));
            setFieldValue('program-edit-program_code', trigger.getAttribute('data-program-code'));
            setFieldValue('program-edit-program_name', trigger.getAttribute('data-program-name'));
            setFieldValue('program-edit-department_id', trigger.getAttribute('data-department-id'));
            setFieldValue('program-edit-program_type', trigger.getAttribute('data-program-type'));
            setFieldValue('program-edit-regulatory_body', trigger.getAttribute('data-regulatory-body') || '');
            setFieldValue('program-edit-duration_months', trigger.getAttribute('data-duration-months') || '12');
            setFieldValue('program-edit-status', trigger.getAttribute('data-status') || 'active');
            setFieldValue('program-edit-homepage_tagline', trigger.getAttribute('data-homepage-tagline') || '');
            setFieldValue('program-edit-entry_requirements', trigger.getAttribute('data-entry-requirements') || '');
            setFieldValue('program-edit-homepage_display_order', trigger.getAttribute('data-homepage-display-order') || '0');
            setFieldValue('program-edit-is_featured_on_homepage', trigger.getAttribute('data-is-featured'));
        }

        document.querySelectorAll('.program-edit-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                fillEditForm(trigger);
            });
        });

        if (document.querySelector('.tich-modal.is-open')) {
            document.body.style.overflow = 'hidden';
        }
    })();
    </script>
@endsection
