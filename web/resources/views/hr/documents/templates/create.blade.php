@extends('layouts.hr')

@section('title', 'New Template')

@php
    $placeholderHelp = 'Use {{variable_name}} for placeholders. Available variables: staff_full_name, staff_job_title, staff_department, staff_employee_number, staff_employment_start_date, staff_contract_end_date, staff_gross_monthly_salary, staff_kra_pin, staff_nssf_number, staff_sha_number, staff_helb_number, institution_name, current_date, current_year';
    $sampleData = [
        '{{staff_full_name}}' => 'John Doe',
        '{{staff_job_title}}' => 'Teacher',
        '{{staff_department}}' => 'Academics',
        '{{staff_employee_number}}' => 'EMP/2024/00001',
        '{{institution_name}}' => 'TICH ERP',
        '{{current_date}}' => 'January 1, 2024',
        '{{staff_gross_monthly_salary}}' => '50,000.00',
        '{{staff_kra_pin}}' => 'A123456789X',
        '{{staff_nssf_number}}' => '123456789',
        '{{staff_sha_number}}' => '987654321',
        '{{staff_helb_number}}' => '555555555',
    ];
@endphp

@section('hr-content')
    <x-page-toolbar title="Create Document Template" />

    <div class="tich-grid tich-grid--2">
        <article class="tich-card">
            <form method="POST" action="{{ route('hr.documents.templates.store') }}">
                @csrf

                <div class="tich-grid tich-grid--2 tich-mb-6">
                    <div>
                        <label for="name" class="tich-label">Template Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required class="tich-input">
                    </div>
                    <div>
                        <label for="type" class="tich-label">Document Type *</label>
                        <select id="type" name="type" required class="tich-input">
                            <option value="">Select type</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="format" class="tich-label">Template Format *</label>
                        <select id="format" name="format" required class="tich-input">
                            <option value="html" {{ old('format', 'html') == 'html' ? 'selected' : '' }}>HTML (PDF output)</option>
                            <option value="docx" {{ old('format') == 'docx' ? 'selected' : '' }}>Word Document (.docx)</option>
                        </select>
                    </div>
                    <div class="tich-grid--span-2">
                        <label for="content" class="tich-label">Template Content (HTML) *</label>
                        <textarea id="content" name="content" rows="20" required class="tich-input" style="font-family: monospace;">{{ old('content') }}</textarea>
                        <p class="tich-caption tich-mt-1">{{ $placeholderHelp }}</p>
                    </div>
                </div>

                <div class="tich-mt-6">
                    <button type="submit" class="tich-btn tich-btn-primary">Create Template</button>
                    <a href="{{ route('hr.documents.templates.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
                </div>
            </form>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Live Preview</h3>
            <p class="tich-text tich-text--secondary tich-mb-4">Preview how your template will look with staff data.</p>
            <div style="border: 1px solid var(--tich-neutral-border); border-radius: var(--radius_md); overflow: hidden; background: #f8fafc; min-height: 400px;">
                <iframe id="preview-frame" src="about:blank" width="100%" height="600px" style="border: none; display: block;"></iframe>
            </div>
            <button type="button" class="tich-btn tich-btn-secondary tich-mt-4" onclick="updatePreview()">Refresh Preview</button>
        </article>
    </div>

    <script>
        const previewFrame = document.getElementById('preview-frame');
        const contentTextarea = document.getElementById('content');
        const sampleData = @json($sampleData);

        function updatePreview() {
            const content = contentTextarea.value;
            const previewHtml = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Preview</title>';
            previewHtml += '<style>';
            previewHtml += '@page { margin: 2cm; }';
            previewHtml += 'body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 900px; margin: 0 auto; padding: 0; background: #fff; }';
            previewHtml += '.doc-container { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }';
            previewHtml += '.doc-header { background: #1e40af; color: white; padding: 25px 30px; }';
            previewHtml += '.doc-header h1 { margin: 0; font-size: 24px; }';
            previewHtml += '.doc-body { padding: 30px; }';
            previewHtml += '.doc-meta { background: #f8fafc; padding: 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #3b82f6; }';
            previewHtml += '.doc-meta-item { margin-bottom: 8px; }';
            previewHtml += '.doc-meta-label { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 600; }';
            previewHtml += '.doc-meta-value { font-weight: 600; color: #111827; }';
            previewHtml += 'h2 { color: #1e40af; font-size: 16px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px; margin-top: 25px; }';
            previewHtml += 'table { width: 100%; border-collapse: collapse; margin-top: 15px; }';
            previewHtml += 'th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; }';
            previewHtml += 'th { background: #f8fafc; font-weight: 600; }';
            previewHtml += '.signature-block { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }';
            previewHtml += '.signature-box { border: 1px solid #e5e7eb; padding: 15px; background: #fafafa; }';
            previewHtml += '.signature-line { border-top: 1px solid #9ca3af; padding-top: 8px; margin-top: 40px; }';
            previewHtml += '@media print { body { margin: 0; } .doc-container { border: none; box-shadow: none; } }';
            previewHtml += '</style></head><body>';
            previewHtml += '<div class="doc-container">';
            previewHtml += '<div class="doc-header"><h1>TICH ERP</h1></div>';
            previewHtml += '<div class="doc-body">';

            let processedContent = content;
            for (const [key, value] of Object.entries(sampleData)) {
                processedContent = processedContent.split(key).join(value);
            }
            previewHtml += processedContent;
            previewHtml += '</div></div></body></html>';

            const blob = new Blob([previewHtml], { type: 'text/html' });
            previewFrame.src = URL.createObjectURL(blob);
        }

        contentTextarea.addEventListener('input', updatePreview);
        document.addEventListener('DOMContentLoaded', updatePreview);
    </script>
@endsection
