@extends('layouts.hr')

@section('title', 'Send Document - ' . $staff->fullName())

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.documents.show', $staff) }}" class="tich-btn tich-btn-ghost">&larr; Back to documents</a>
    </div>

    <article class="tich-card">
        <h1 class="tich-h1 tich-mb-6">Send Document to {{ $staff->fullName() }}</h1>

        <form method="POST" action="{{ route('hr.staff.documents.send.store', $staff) }}">
            @csrf

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="template_id" class="tich-label">Select Template *</label>
                    <select id="template_id" name="template_id" required class="tich-input">
                        <option value="">Select template</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}" {{ (old('template_id') == $template->id || request('template_id') == $template->id) ? 'selected' : '' }}>
                                {{ $template->name }} ({{ ucfirst(str_replace('_', ' ', $template->type)) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="document_name" class="tich-label">Document Name *</label>
                    <input type="text" id="document_name" name="document_name" value="{{ old('document_name') }}" required class="tich-input">
                </div>
                <div class="tich-grid--span-2">
                    <label class="tich-label">Document Preview</label>
                    <div style="border: 1px solid var(--tich-neutral-border); border-radius: var(--radius-md); overflow: hidden; background: #f8fafc; min-height: 400px;">
                        <iframe id="template-preview" src="about:blank" width="100%" height="500px" style="border: none; display: block;"></iframe>
                    </div>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Send Document to Staff</button>
                <a href="{{ route('hr.documents.show', $staff) }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>

    <script>
        const templates = @json($templates->mapWithKeys(fn($t) => [$t->id => $t]));
        const staffData = @json([
            'full_name' => $staff->fullName(),
            'job_title' => $staff->job_title,
            'department' => $staff->department->dept_name ?? '',
            'employee_number' => $staff->employee_number,
            'institution_name' => '{{ config("app.name", "TICH ERP") }}',
            'current_date' => '{{ now()->format("F j, Y") }}',
        ]);

        function previewTemplate(templateId) {
            const previewFrame = document.getElementById('template-preview');
            const nameInput = document.getElementById('document_name');

            if (!templateId || !templates[templateId]) {
                previewFrame.src = 'about:blank';
                return;
            }

            const generateUrl = '{{ route('hr.documents.templates.generate', ['template' => 0, 'staff_id' => $staff->id]) }}'.replace('/0', '/' + templateId);
            previewFrame.src = generateUrl;

            const selectedTemplate = templates[templateId];
            if (selectedTemplate) {
                nameInput.value = selectedTemplate.name + ' - ' + staffData.full_name;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const templateId = urlParams.get('template_id');
            const select = document.getElementById('template_id');

            if (templateId && select) {
                select.value = templateId;
                previewTemplate(templateId);
            } else {
                const selected = document.querySelector('#template_id option:checked');
                if (selected && selected.value) {
                    previewTemplate(selected.value);
                }
            }
        });
    </script>
@endsection
