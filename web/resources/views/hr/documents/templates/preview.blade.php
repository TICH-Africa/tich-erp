@extends('layouts.hr')

@section('title', 'Preview: ' . $template->name)

@section('hr-content')
    <div class="tich-mb-8">
        <a href="{{ route('hr.documents.templates.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to templates</a>
        <a href="{{ route('hr.documents.templates.generate', ['template' => $template, 'staff_id' => $staff->id]) }}" class="tich-btn tich-btn-primary tich-ml-4" target="_blank">Print / Save PDF</a>
    </div>

    <div class="tich-card">
        <h3 class="tich-h3 tich-mb-4">Preview for {{ $staff->fullName() }}</h3>
        <div class="tich-mt-4" style="border: 1px solid var(--tich-neutral-border); border-radius: var(--radius-md); overflow: hidden; background: #f8fafc;">
            <iframe src="{{ route('hr.documents.templates.generate', ['template' => $template, 'staff_id' => $staff->id]) }}" width="100%" height="800px" style="border: none; display: block;"></iframe>
        </div>
    </div>
@endsection
