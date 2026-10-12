@extends('layouts.hr')

@section('title', 'Send policy - ' . $policy->title)

@section('hr-content')
    <x-page-toolbar title="Send policy" :meta="$policy->title . ' · HR Policies'">
        <x-slot:actions>
            <a href="{{ route('hr.policies.show', $policy) }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.policies.send.store', $policy) }}" class="tich-form-stack">
            @csrf
            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div class="tich-grid--span-2">
                    <label class="tich-label">Policy</label>
                    <input type="text" value="{{ $policy->title }} (v{{ $policy->version }})" disabled class="tich-input">
                </div>

                <div class="tich-grid--span-2">
                    <label class="tich-label">Send to</label>
                    <select name="target_type" id="target_type" class="tich-input" required>
                        <option value="staff" {{ $policy->target_type === 'staff' ? 'selected' : '' }}>Specific staff members</option>
                        <option value="department" {{ $policy->target_type === 'department' ? 'selected' : '' }}>Department(s)</option>
                        <option value="all" {{ $policy->target_type === 'all' ? 'selected' : '' }}>All staff</option>
                    </select>
                </div>

                <div id="staff-select" class="tich-grid--span-2" style="display: {{ $policy->target_type === 'staff' ? 'block' : 'none' }};">
                    <label class="tich-label">Select staff members *</label>
                    <div class="tich-checkbox-grid" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                        @foreach ($staffList as $staff)
                            <label class="tich-checkbox-item">
                                <input type="checkbox" name="staff_ids[]" value="{{ $staff->id }}" {{ in_array($staff->id, $policy->target_staff_ids ?? []) ? 'checked' : '' }}>
                                <span>{{ $staff->fullName() }} ({{ $staff->employee_number }}) · {{ $staff->job_title ?? 'Staff' }} · {{ $staff->department?->dept_name ?? 'No Dept' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div id="department-select" class="tich-grid--span-2" style="display: {{ $policy->target_type === 'department' ? 'block' : 'none' }};">
                    <label class="tich-label">Select department(s) *</label>
                    <div class="tich-checkbox-grid" style="max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                        @foreach ($departments as $dept)
                            <label class="tich-checkbox-item">
                                <input type="checkbox" name="target_departments[]" value="{{ $dept->id }}" {{ in_array($dept->id, $policy->target_departments ?? []) ? 'checked' : '' }}>
                                <span>{{ $dept->dept_name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Send policy</button>
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
            const staffSelect = document.getElementById('staff-select');
            const deptSelect = document.getElementById('department-select');
            const form = document.querySelector('form[action*="hr.policies.send.store"]');

            function toggleSections() {
                const type = targetType.value;

                // Hide all sections first
                staffSelect.style.display = 'none';
                deptSelect.style.display = 'none';

                // Show the selected section
                if (type === 'staff') {
                    staffSelect.style.display = 'block';
                } else if (type === 'department') {
                    deptSelect.style.display = 'block';
                }
                // 'all' shows no additional sections

                // Remove required from ALL checkboxes in ALL sections
                document.querySelectorAll('#staff-select input[type="checkbox"], #department-select input[type="checkbox"]').forEach(input => {
                    input.required = false;
                });
            }

            function validateSelection() {
                const type = targetType.value;
                let fieldId = '';
                if (type === 'staff') fieldId = 'staff-select';
                else if (type === 'department') fieldId = 'department-select';
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

            targetType.addEventListener('change', toggleSections);
            toggleSections();

            // Form submit validation
            form.addEventListener('submit', function(e) {
                if (!validateSelection()) {
                    e.preventDefault();
                    const type = targetType.value;
                    let fieldId = '';
                    let label = '';
                    if (type === 'staff') { fieldId = 'staff-select'; label = 'staff members'; }
                    else if (type === 'department') { fieldId = 'department-select'; label = 'departments'; }
                    showError(fieldId, 'Please select at least one ' + label + '.');
                }
            });

            // Clear error when user checks a box
            document.querySelectorAll('#staff-select input[type="checkbox"], #department-select input[type="checkbox"]').forEach(input => {
                input.addEventListener('change', function() {
                    const fieldId = this.closest('div[id$="-select"]').id;
                    if (fieldId) clearError(fieldId);
                });
            });
        });
    </script>
@endsection
