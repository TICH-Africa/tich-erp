@extends('layouts.hr')

@section('title', 'Document Templates')

@section('hr-content')
    <x-page-toolbar title="Document Templates" meta="Templates for contracts, letters, and clearances">
        <x-slot:actions>
            <a href="{{ route('hr.documents.templates.create') }}" class="tich-btn tich-btn-primary">+ New Template</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mb-6">
        <div class="tich-grid tich-grid--4">
            <div>
                <label for="template-staff-id" class="tich-label">Select Staff Member</label>
                <select id="template-staff-id" class="tich-input">
                    <option value="">-- Select staff --</option>
                    @php
                        $allStaff = \App\Models\Staff::orderBy('first_name')->get(['id', 'first_name', 'surname', 'employee_number']);
                    @endphp
                    @foreach ($allStaff as $s)
                        <option value="{{ $s->id }}">{{ $s->fullName() }} ({{ $s->employee_number }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="tich-caption tich-mt-2">Select a staff member to enable Generate, Download, and Send actions.</p>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table" id="templates-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td>
                                <div class="tich-flex tich-flex--center">
                                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #1e40af, #3b82f6); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; margin-right: 12px; flex-shrink: 0;">
                                        {{ strtoupper(substr($template->name, 0, 2)) }}
                                    </div>
                                    <strong>{{ $template->name }}</strong>
                                </div>
                            </td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $template->type)) }}</td>
                            <td class="tich-caption">
                                <span class="tich-badge tich-badge--info tich-badge--sm">
                                    {{ $template->format === 'docx' ? 'Word' : 'HTML' }}
                                </span>
                            </td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $template->is_active ? 'success' : 'warning' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $template->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="tich-flex tich-flex--gap tich-flex--wrap">
                                    <a href="{{ route('hr.documents.templates.edit', $template) }}" class="tich-btn tich-btn-ghost tich-btn--sm">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Edit
                                    </a>
                                    <button type="button" class="tich-btn tich-btn-secondary tich-btn--sm template-action" data-template="{{ $template->id }}" data-action="preview">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        Preview
                                    </button>
                                    <button type="button" class="tich-btn tich-btn-primary tich-btn--sm template-action" data-template="{{ $template->id }}" data-action="generate">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                                        Generate
                                    </button>
                                    <button type="button" class="tich-btn tich-btn-success tich-btn--sm template-action" data-template="{{ $template->id }}" data-action="download">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                        Download
                                    </button>
                                    <button type="button" class="tich-btn tich-btn-blue tich-btn--sm template-action" data-template="{{ $template->id }}" data-action="send">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                                        Send
                                    </button>
                                    <form method="POST" action="{{ route('hr.documents.templates.destroy', $template) }}" onsubmit="return confirm('Delete this template? This cannot be undone.')" class="tich-flex tich-flex--center">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--danger">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-table-empty">
                                <div class="tich-flex tich-flex--center" style="padding: 40px 0; flex-direction: column;">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: #9ca3af; margin-bottom: 12px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <p style="color: #6b7280; font-size: 14px;">No templates found. Create your first template to get started.</p>
                                    <a href="{{ route('hr.documents.templates.create') }}" class="tich-btn tich-btn-primary tich-mt-4">+ Create Template</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .tich-admin-table thead th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }
        .tich-admin-table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }
        .tich-admin-table tbody tr:hover {
            background: #f9fafb;
        }
        .tich-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .tich-flex--wrap {
            flex-wrap: wrap;
        }
        .tich-table-empty {
            text-align: center;
            padding: 60px 20px;
        }
    </style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const staffSelect = document.getElementById('template-staff-id');
    const buttons = document.querySelectorAll('.template-action');

    function updateButtonStates() {
        const staffId = staffSelect.value;
        buttons.forEach(btn => {
            const action = btn.dataset.action;
            if (action === 'edit' || action === 'delete') {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            } else {
                if (!staffId) {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    btn.style.pointerEvents = 'none';
                } else {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                }
            }
        });
    }

    updateButtonStates();

    staffSelect.addEventListener('change', updateButtonStates);

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const templateId = this.dataset.template;
            const action = this.dataset.action;
            const staffId = staffSelect.value;

            if (!staffId) {
                alert('Please select a staff member first.');
                staffSelect.focus();
                return;
            }

            if (action === 'send') {
                window.location.href = `/hr/staff/${staffId}/documents/send?template_id=${templateId}`;
            } else if (action === 'preview') {
                window.open(`/hr/documents/templates/${templateId}/preview?staff_id=${staffId}`, '_blank', 'width=900,height=800');
            } else if (action === 'generate') {
                window.open(`/hr/documents/templates/${templateId}/generate?staff_id=${staffId}`, '_blank', 'width=900,height=800');
            } else if (action === 'download') {
                window.location.href = `/hr/documents/templates/${templateId}/download?staff_id=${staffId}`;
            }
        });
    });
});
</script>
@endsection
