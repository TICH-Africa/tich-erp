@extends('layouts.hr')

@section('title', 'Edit Policy')

@section('hr-content')
    <x-page-toolbar title="Edit HR Policy" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.policies.update', $policy) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="title" class="tich-label">Policy Title *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $policy->title) }}" required class="tich-input">
                </div>
                <div>
                    <label for="category" class="tich-label">Category *</label>
                    <select id="category" name="category" required class="tich-input">
                        <option value="">Select category</option>
                        <option value="general" {{ old('category', $policy->category) == 'general' ? 'selected' : '' }}>General</option>
                        <option value="leave" {{ old('category', $policy->category) == 'leave' ? 'selected' : '' }}>Leave</option>
                        <option value="conduct" {{ old('category', $policy->category) == 'conduct' ? 'selected' : '' }}>Conduct</option>
                        <option value="benefits" {{ old('category', $policy->category) == 'benefits' ? 'selected' : '' }}>Benefits</option>
                        <option value="safety" {{ old('category', $policy->category) == 'safety' ? 'selected' : '' }}>Safety</option>
                        <option value="other" {{ old('category', $policy->category) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="tich-grid--span-2">
                    <label for="description" class="tich-label">Description</label>
                    <textarea id="description" name="description" rows="3" class="tich-input">{{ old('description', $policy->description) }}</textarea>
                </div>
                <div>
                    <label for="file" class="tich-label">Replace Document</label>
                    <input type="file" id="file" name="file" class="tich-input">
                    <p class="tich-caption tich-mt-1">Leave empty to keep current file. Max: 10MB.</p>
                    @if ($policy->file_path)
                        <p class="tich-caption tich-mt-1">Current: {{ $policy->original_filename }}</p>
                    @endif
                </div>
                <div>
                    <label for="effective_date" class="tich-label">Effective Date</label>
                    <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', $policy->effective_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div>
                    <label for="expiry_date" class="tich-label">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $policy->expiry_date?->format('Y-m-d')) }}" class="tich-input">
                </div>
                <div>
                    <label for="tags" class="tich-label">Tags</label>
                    <input type="text" id="tags" name="tags" value="{{ old('tags', $policy->tags) }}" placeholder="e.g. leave, annual, 2024" class="tich-input">
                </div>
                <div class="tich-flex--center">
                    <label class="tich-checkbox">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $policy->is_active) ? 'checked' : '' }}>
                        <span>Active</span>
                    </label>
                </div>

                <div class="tich-grid--span-2">
                    <label for="target_type" class="tich-label">Target Audience *</label>
                    <select id="target_type" name="target_type" required class="tich-input">
                        <option value="all" {{ old('target_type', $policy->target_type === 'role' ? 'all' : $policy->target_type) == 'all' ? 'selected' : '' }}>All staff</option>
                        <option value="staff" {{ old('target_type', $policy->target_type === 'role' ? 'all' : $policy->target_type) == 'staff' ? 'selected' : '' }}>Specific staff members</option>
                        <option value="department" {{ old('target_type', $policy->target_type === 'role' ? 'all' : $policy->target_type) == 'department' ? 'selected' : '' }}>Department(s)</option>
                    </select>
                </div>

                <div id="target_staff_field" class="tich-grid--span-2" style="display: {{ old('target_type', $policy->target_type) == 'staff' ? 'block' : 'none' }};">
                    <label class="tich-label">Specific Staff Members</label>
                    <div class="tich-checkbox-grid" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                        @foreach ($staffList as $staff)
                            <label class="tich-checkbox-item">
                                <input type="checkbox" name="target_staff_ids[]" value="{{ $staff->id }}" {{ in_array($staff->id, old('target_staff_ids', $policy->target_staff_ids ?? [])) ? 'checked' : '' }}>
                                <span>{{ $staff->fullName() }} ({{ $staff->employee_number }}) · {{ $staff->job_title ?? 'Staff' }} · {{ $staff->department?->dept_name ?? 'No Dept' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div id="target_departments_field" class="tich-grid--span-2" style="display: {{ old('target_type', $policy->target_type) == 'department' ? 'block' : 'none' }};">
                    <label class="tich-label">Department(s)</label>
                    <div class="tich-checkbox-grid" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                        @foreach ($departments as $dept)
                            <label class="tich-checkbox-item">
                                <input type="checkbox" name="target_departments[]" value="{{ $dept->id }}" {{ in_array($dept->id, old('target_departments', $policy->target_departments ?? [])) ? 'checked' : '' }}>
                                <span>{{ $dept->dept_name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update Policy</button>
                <a href="{{ route('hr.policies.show', $policy) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>

    <style>
        .tich-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 8px;
        }
        .tich-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            background: #f9fafb;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .tich-checkbox-item:hover {
            background: #f3f4f6;
        }
        .tich-checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #2563eb;
            flex-shrink: 0;
        }
        .tich-checkbox-item span {
            font-size: 0.875rem;
            color: #374151;
            word-break: break-word;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const targetType = document.getElementById('target_type');
            const staffField = document.getElementById('target_staff_field');
            const deptField = document.getElementById('target_departments_field');
            const form = document.querySelector('form[action*="hr.policies.update"]');

            function toggleFields() {
                const type = targetType.value;

                // Hide all fields first
                staffField.style.display = 'none';
                deptField.style.display = 'none';

                // Show the selected field
                if (type === 'staff') {
                    staffField.style.display = 'block';
                } else if (type === 'department') {
                    deptField.style.display = 'block';
                }
                // 'all' shows no additional fields

                // Remove required from ALL checkboxes in ALL fields
                document.querySelectorAll('#target_staff_field input[type="checkbox"], #target_departments_field input[type="checkbox"]').forEach(input => {
                    input.required = false;
                });
            }

            function validateSelection() {
                const type = targetType.value;
                let fieldId = '';
                if (type === 'staff') fieldId = 'target_staff_field';
                else if (type === 'department') fieldId = 'target_departments_field';
                else return true; // 'all' requires no selection

                const field = document.getElementById(fieldId);
                const checked = field.querySelectorAll('input[type="checkbox"]:checked').length;
                return checked > 0;
            }

            function showError(fieldId, message) {
                const field = document.getElementById(fieldId);
                const existingError = field.querySelector('.tich-error');
                if (existingError) existingError.remove();

                const errorEl = document.createElement('span');
                errorEl.className = 'tich-error';
                errorEl.style.color = '#dc2626';
                errorEl.style.fontSize = '0.875rem';
                errorEl.style.marginLeft = '8px';
                errorEl.textContent = message;
                field.querySelector('label').appendChild(errorEl);
            }

            function clearError(fieldId) {
                const field = document.getElementById(fieldId);
                const existingError = field.querySelector('.tich-error');
                if (existingError) existingError.remove();
            }

            targetType.addEventListener('change', toggleFields);
            toggleFields();

            // Form submit validation
            form.addEventListener('submit', function(e) {
                if (!validateSelection()) {
                    e.preventDefault();
                    const type = targetType.value;
                    let fieldId = '';
                    let label = '';
                    if (type === 'staff') { fieldId = 'target_staff_field'; label = 'staff members'; }
                    else if (type === 'department') { fieldId = 'target_departments_field'; label = 'departments'; }
                    showError(fieldId, 'Please select at least one ' + label + '.');
                }
            });

            // Clear error when user checks a box
            document.querySelectorAll('#target_staff_field input[type="checkbox"], #target_departments_field input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', function() {
                    const fieldId = this.closest('div[id^="target_"]').id;
                    if (fieldId) clearError(fieldId);
                });
            });
        });
    </script>
@endsection
