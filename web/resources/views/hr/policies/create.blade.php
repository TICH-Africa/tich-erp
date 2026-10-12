@extends('layouts.hr')

@section('title', 'Upload Policy')

@section('hr-content')
    <x-page-toolbar title="Upload HR Policy" />

    <div class="uf-form">
        <form method="POST" action="{{ route('hr.policies.store') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">POL · HR policy</div>
                    <div class="uf-amount-bar__sum">Upload policy</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Policy Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Policy Title <span class="uf-req">*</span></label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" required class="{{ $errors->has('title') ? 'is-invalid' : '' }}">
                            @error('title')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="uf-field">
                            <label for="category">Category <span class="uf-req">*</span></label>
                            <select id="category" name="category" required class="{{ $errors->has('category') ? 'is-invalid' : '' }}">
                                <option value="">Select category</option>
                                <option value="general" @selected(old('category') == 'general')>General</option>
                                <option value="leave" @selected(old('category') == 'leave')>Leave</option>
                                <option value="conduct" @selected(old('category') == 'conduct')>Conduct</option>
                                <option value="benefits" @selected(old('category') == 'benefits')>Benefits</option>
                                <option value="safety" @selected(old('category') == 'safety')>Safety</option>
                                <option value="other" @selected(old('category') == 'other')>Other</option>
                            </select>
                            @error('category')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="file">Policy Document <span class="uf-req">*</span></label>
                            <input type="file" id="file" name="file" required class="{{ $errors->has('file') ? 'is-invalid' : '' }}">
                            @error('file')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                            <span class="uf-hint">Max file size: 10MB. PDF, DOC, DOCX, images accepted.</span>
                        </div>
                        <div class="uf-field">
                            <label for="effective_date">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date') }}">
                        </div>
                        <div class="uf-field">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}">
                        </div>
                        <div class="uf-field">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" value="{{ old('tags') }}" placeholder="e.g. leave, annual, 2024">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Target Audience</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="target_type">Send to <span class="uf-req">*</span></label>
                            <select id="target_type" name="target_type" required class="{{ $errors->has('target_type') ? 'is-invalid' : '' }}">
                                <option value="all" @selected(old('target_type') == 'all')>All staff</option>
                                <option value="staff" @selected(old('target_type') == 'staff')>Specific staff members</option>
                                <option value="department" @selected(old('target_type') == 'department')>Department(s)</option>
                            </select>
                            @error('target_type')
                                <span class="uf-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div id="target_staff_field" style="display: none;">
                        <div class="uf-field">
                            <label>Select staff members <span class="uf-req">*</span></label>
                            <div class="tich-checkbox-grid" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                                @foreach ($staffList ?? [] as $staff)
                                    <label class="tich-checkbox-item">
                                        <input type="checkbox" name="target_staff_ids[]" value="{{ $staff->id }}" {{ in_array($staff->id, old('target_staff_ids', [])) ? 'checked' : '' }}>
                                        <span>{{ $staff->fullName() }} ({{ $staff->employee_number }}) · {{ $staff->job_title ?? 'Staff' }} · {{ $staff->department?->dept_name ?? 'No Dept' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div id="target_departments_field" style="display: none;">
                        <div class="uf-field">
                            <label>Select department(s) <span class="uf-req">*</span></label>
                            <div class="tich-checkbox-grid" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                                @foreach ($departments ?? [] as $dept)
                                    <label class="tich-checkbox-item">
                                        <input type="checkbox" name="target_departments[]" value="{{ $dept->id }}" {{ in_array($dept->id, old('target_departments', [])) ? 'checked' : '' }}>
                                        <span>{{ $dept->dept_name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Upload Policy</button>
                        <a href="{{ route('hr.policies.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

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
            const form = document.querySelector('form[data-uf="ready"]');

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
                // Remove existing error
                const existingError = field.querySelector('.uf-error');
                if (existingError) existingError.remove();

                const errorEl = document.createElement('span');
                errorEl.className = 'uf-error';
                errorEl.textContent = message;
                field.querySelector('label').appendChild(errorEl);
            }

            function clearError(fieldId) {
                const field = document.getElementById(fieldId);
                const existingError = field.querySelector('.uf-error');
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
