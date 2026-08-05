@extends('layouts.hr')

@section('title', 'Preview: ' . $template->name)

@section('hr-content')
    <x-page-toolbar :title="'Preview: ' . $template->name" :meta="'Preview for ' . $staff->fullName()">
        <x-slot:actions>
            <a href="{{ route('hr.documents.templates.generate', ['template' => $template, 'staff_id' => $staff->id]) }}" class="tich-btn tich-btn-primary" target="_blank">Print / Save PDF</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <div class="tich-mt-4" style="border: 1px solid var(--tich-neutral-border); border-radius: var(--radius-md); overflow: hidden; background: #f8fafc;">
            <iframe src="{{ route('hr.documents.templates.generate', ['template' => $template, 'staff_id' => $staff->id]) }}" width="100%" height="800px" style="border: none; display: block;"></iframe>
        </div>
    </div>
@endsection
