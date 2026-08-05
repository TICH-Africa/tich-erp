@extends('layouts.hr')

@section('title', 'Edit Template')

@section('hr-content')
    <x-page-toolbar title="Edit Document Template" />

    <article class="tich-card">
        <form method="POST" action="{{ route('hr.documents.templates.update', $template) }}">
            @csrf
            @method('PUT')

            <div class="tich-grid tich-grid--2 tich-mb-6">
                <div>
                    <label for="name" class="tich-label">Template Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $template->name) }}" required class="tich-input">
                </div>
                <div>
                    <label for="type" class="tich-label">Document Type *</label>
                    <select id="type" name="type" required class="tich-input">
                        <option value="">Select type</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" {{ old('type', $template->type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="tich-checkbox">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                        <span>Active</span>
                    </label>
                </div>
                <div class="tich-grid--span-2">
                    <label for="content" class="tich-label">Template Content (HTML) *</label>
                    <textarea id="content" name="content" rows="20" required class="tich-input" style="font-family: monospace;">{{ old('content', $template->content) }}</textarea>
                    <p class="tich-caption tich-mt-1">Use @{{variable_name}} for placeholders. Available variables: staff_full_name, staff_job_title, staff_department, staff_employee_number, staff_employment_start_date, staff_contract_end_date, staff_gross_monthly_salary, staff_kra_pin, staff_nssf_number, staff_sha_number, staff_helb_number, institution_name, current_date, current_year</p>
                </div>
            </div>

            <div class="tich-mt-6">
                <button type="submit" class="tich-btn tich-btn-primary">Update Template</button>
                <a href="{{ route('hr.documents.templates.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </article>

    <article class="tich-card tich-mt-8">
        <h3 class="tich-h3">Template Structure Reference</h3>
        <p class="tich-text tich-text--secondary tich-mb-4">{{ $templateStructure }}</p>
    </article>

    @php
        $previewStaff = \App\Models\Staff::first();
        $previewContent = null;
        if ($previewStaff) {
            $service = new \App\Services\DocumentGenerationService();
            $previewContent = $service->populateTemplate($template, $previewStaff);
        }
    @endphp
    @if ($previewStaff && $previewContent)
        <article class="tich-card tich-mt-8">
            <h3 class="tich-h3">Live Preview</h3>
            <p class="tich-text tich-text--secondary tich-mb-4">Previewing with: <strong>{{ $previewStaff->fullName() }}</strong> ({{ $previewStaff->employee_number }})</p>
            <div style="border: 1px solid var(--tich-neutral-border); border-radius: var(--radius-md); overflow: hidden; background: #f8fafc; min-height: 300px;">
                {!! $previewContent !!}
            </div>
            <div class="tich-mt-4">
                <a href="{{ route('hr.documents.templates.generate', ['template' => $template, 'staff_id' => $previewStaff->id]) }}" class="tich-btn tich-btn-secondary" target="_blank">Open Full Preview</a>
                <a href="{{ route('hr.documents.templates.download', ['template' => $template, 'staff_id' => $previewStaff->id]) }}" class="tich-btn tich-btn-success tich-ml-2">Download</a>
            </div>
        </article>
    @endif
@endsection
