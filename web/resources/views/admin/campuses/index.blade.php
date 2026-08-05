@extends('layouts.admin')

@section('title', 'Campuses')

@section('admin-content')
    @php
        $openCreateModal = $errors->any() && old('_method') !== 'PUT';
        $openEditCampusId = old('_method') === 'PUT' ? (int) old('edit_campus_id') : null;
        $editCampus = $openEditCampusId ? $campuses->firstWhere('id', $openEditCampusId) : null;
    @endphp

    <x-page-toolbar title="Campuses" meta="Multi-campus structure for TICH hubs">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="campus-create-modal">
                Add campus
            </button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <h2 class="tich-h3">All campuses ({{ $campuses->count() }})</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Parent</th>
                    <th>County</th>
                    <th>Status</th>
                    <th style="width: 4rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($campuses as $campus)
                    <tr>
                        <td>{{ $campus->campus_code }}</td>
                        <td>{{ $campus->campus_name }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($campus->campus_type)) }}</td>
                        <td>{{ $campus->parentCampus?->campus_name ?? '-' }}</td>
                        <td>{{ $campus->county ?? '-' }}</td>
                        <td>{{ $campus->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            <button
                                type="button"
                                class="tich-squircle-btn campus-edit-trigger"
                                title="Edit campus"
                                aria-label="Edit {{ $campus->campus_name }}"
                                data-open-modal="campus-edit-modal"
                                data-update-url="{{ route('admin.campuses.update', $campus) }}"
                                data-campus-id="{{ $campus->id }}"
                                data-campus-code="{{ $campus->campus_code }}"
                                data-campus-name="{{ $campus->campus_name }}"
                                data-campus-type="{{ $campus->campus_type }}"
                                data-parent-campus-id="{{ $campus->parent_campus_id }}"
                                data-county="{{ $campus->county }}"
                                data-sub-county="{{ $campus->sub_county }}"
                                data-physical-address="{{ $campus->physical_address }}"
                                data-is-active="{{ $campus->is_active ? '1' : '0' }}"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No campuses yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.partials.campus-create-modal', [
        'parentCampuses' => $parentCampuses,
        'campusTypes' => $campusTypes,
        'open' => $openCreateModal,
    ])

    <div
        id="campus-edit-modal"
        class="tich-modal{{ $openEditCampusId ? ' is-open' : '' }}"
        aria-hidden="{{ $openEditCampusId ? 'false' : 'true' }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="campus-edit-modal-title"
    >
        <div class="tich-modal__backdrop" data-close-modal="campus-edit-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 id="campus-edit-modal-title" class="tich-h3" style="margin: 0;">Edit campus</h2>
                <button type="button" class="tich-modal__close" data-close-modal="campus-edit-modal" aria-label="Close">&times;</button>
            </header>
            <form
                id="campus-edit-form"
                method="POST"
                action="{{ $editCampus ? route('admin.campuses.update', $editCampus) : '#' }}"
                class="tich-modal__body"
            >
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_campus_id" id="campus-edit-id" value="{{ old('edit_campus_id') }}">
                @if ($errors->any() && old('_method') === 'PUT')
                    <div class="tich-modal__errors">
                        <ul style="margin: 0; padding-left: 1.25rem;">
                            @foreach ($errors->all() as $error)
                                <li class="tich-text">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @include('admin.partials.campus-form-fields', [
                    'parentCampuses' => $parentCampuses,
                    'campusTypes' => $campusTypes,
                    'campus' => $editCampus,
                    'fieldIdPrefix' => 'campus-edit-',
                    'excludeCampusId' => $editCampus?->id,
                ])
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="campus-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save changes</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')

    <script>
    (function () {
        var form = document.getElementById('campus-edit-form');
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
            setFieldValue('campus-edit-id', trigger.getAttribute('data-campus-id'));
            setFieldValue('campus-edit-campus_code', trigger.getAttribute('data-campus-code'));
            setFieldValue('campus-edit-campus_name', trigger.getAttribute('data-campus-name'));
            setFieldValue('campus-edit-campus_type', trigger.getAttribute('data-campus-type'));
            setFieldValue('campus-edit-parent_campus_id', trigger.getAttribute('data-parent-campus-id') || '');
            setFieldValue('campus-edit-county', trigger.getAttribute('data-county') || '');
            setFieldValue('campus-edit-sub_county', trigger.getAttribute('data-sub-county') || '');
            setFieldValue('campus-edit-physical_address', trigger.getAttribute('data-physical-address') || '');
            setFieldValue('campus-edit-is_active', trigger.getAttribute('data-is-active'));
        }

        document.querySelectorAll('.campus-edit-trigger').forEach(function (trigger) {
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
