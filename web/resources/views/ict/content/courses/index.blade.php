@extends('layouts.ict')

@section('title', 'Courses & programmes')

@section('ict-content')
    @php
        $openCreateModal = $errors->any() && old('_form') === 'create';
        $openEditProgramId = old('_form') === 'edit' ? (int) old('edit_program_id') : null;
        $editProgram = $openEditProgramId ? $programs->firstWhere('id', $openEditProgramId) : null;
    @endphp

    <x-page-toolbar title="Courses &amp; programmes" meta="Public programmes catalogue and homepage features">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="course-create-modal">Add course</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>Featured</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr>
                        <td>
                            @if ($program->coverImageUrl())
                                <img src="{{ $program->coverImageUrl() }}" alt="" class="tich-program-admin-thumb">
                            @else
                                <span class="tich-caption">-</span>
                            @endif
                        </td>
                        <td>{{ $program->program_code }}</td>
                        <td>{{ $program->program_name }}</td>
                        <td>{{ $program->department?->dept_name ?? '-' }}</td>
                        <td>
                            @if ($program->isTieredProgram())
                                <span class="tich-badge">Tiered</span>
                            @else
                                {{ ucfirst(str_replace('_', ' ', $program->program_type)) }}
                            @endif
                        </td>
                        <td>{{ $program->is_featured_on_homepage ? 'Yes' : '-' }}</td>
                        <td>
                            <button
                                type="button"
                                class="tich-squircle-btn course-edit-trigger"
                                data-open-modal="course-edit-modal"
                                data-update-url="{{ route('ict.content.courses.update', $program) }}"
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
                                data-is-tiered="{{ $program->is_tiered ? '1' : '0' }}"
                                data-tier-semester-months="{{ $program->tier_semester_months ?? '' }}"
                                data-tiers-json="{{ e(json_encode($program->tiers->sortBy('tier_order')->map(fn ($t) => [
                                    'tier_key' => $t->tier_key,
                                    'cumulative_months' => $t->cumulative_months,
                                    'learning_outcomes' => $t->learning_outcomes,
                                    'marketing_summary' => $t->marketing_summary,
                                ])->values()->all())) }}"
                            >Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No courses yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="course-create-modal" class="tich-modal{{ $openCreateModal ? ' is-open' : '' }}" aria-hidden="{{ $openCreateModal ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="course-create-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header"><h2 class="tich-h3">Add course</h2><button type="button" class="tich-squircle-btn" data-close-modal="course-create-modal">×</button></div>
            <form method="POST" action="{{ route('ict.content.courses.store') }}" class="tich-modal__body" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_form" value="create">
                @if ($errors->any() && old('_form') === 'create')
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
                    'fieldIdPrefix' => 'create_',
                ])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="course-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="course-edit-modal" class="tich-modal{{ $editProgram ? ' is-open' : '' }}" aria-hidden="{{ $editProgram ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="course-edit-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header"><h2 class="tich-h3">Edit course</h2><button type="button" class="tich-squircle-btn" data-close-modal="course-edit-modal">×</button></div>
            <form id="course-edit-form" method="POST" action="{{ $editProgram ? route('ict.content.courses.update', $editProgram) : '#' }}" class="tich-modal__body" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="edit_program_id" id="edit_program_id" value="{{ old('edit_program_id', $editProgram?->id) }}">
                @include('admin.partials.program-form-fields', [
                    'program' => $editProgram,
                    'learningDepartments' => $learningDepartments,
                    'programTypes' => $programTypes,
                    'programStatuses' => $programStatuses,
                    'fieldIdPrefix' => 'edit_',
                ])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="course-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    @include('admin.partials.tich-modal-assets')
    <x-asset.script path="js/tich-cms-editor.js" :defer="false" />
    <script>
        document.querySelectorAll('.course-edit-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = document.getElementById('course-edit-form');
                form.action = btn.getAttribute('data-update-url');
                document.getElementById('edit_program_id').value = btn.getAttribute('data-program-id') || '';
                var fields = {
                    'program-code': 'edit_program_code',
                    'program-name': 'edit_program_name',
                    'department-id': 'edit_department_id',
                    'program-type': 'edit_program_type',
                    'regulatory-body': 'edit_regulatory_body',
                    'duration-months': 'edit_duration_months',
                    'status': 'edit_status',
                    'homepage-display-order': 'edit_homepage_display_order'
                };
                Object.keys(fields).forEach(function (k) {
                    var el = document.getElementById(fields[k]);
                    if (el) el.value = btn.getAttribute('data-' + k) || '';
                });
                if (window.tichCmsEditor) {
                    window.tichCmsEditor.setHtml('edit_homepage_tagline', btn.getAttribute('data-homepage-tagline') || '');
                    window.tichCmsEditor.setHtml('edit_entry_requirements', btn.getAttribute('data-entry-requirements') || '');
                }
                var feat = document.getElementById('edit_is_featured_on_homepage');
                if (feat) feat.checked = btn.getAttribute('data-is-featured') === '1';
                var tierToggle = document.getElementById('edit_is_tiered');
                if (tierToggle) {
                    tierToggle.checked = btn.getAttribute('data-is-tiered') === '1';
                    tierToggle.dispatchEvent(new Event('change', { bubbles: true }));
                }
                var tierMonths = document.getElementById('edit_tier_semester_months');
                if (tierMonths) tierMonths.value = btn.getAttribute('data-tier-semester-months') || '';
                var tierRoot = form.querySelector('[data-program-tier-root]');
                if (tierRoot) {
                    try {
                        var tiers = JSON.parse(btn.getAttribute('data-tiers-json') || '[]');
                        tiers.forEach(function (row, index) {
                            var cum = tierRoot.querySelector('[name="tiers[' + index + '][cumulative_months]"]');
                            if (cum && row.cumulative_months != null) {
                                cum.value = row.cumulative_months;
                                cum.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            var lo = tierRoot.querySelector('[name="tiers[' + index + '][learning_outcomes]"]');
                            var ms = tierRoot.querySelector('[name="tiers[' + index + '][marketing_summary]"]');
                            if (lo) lo.value = row.learning_outcomes || '';
                            if (ms) ms.value = row.marketing_summary || '';
                        });
                    } catch (e) {}
                }
            });
        });
    </script>
    @endpush
@endsection
