@extends('layouts.administration')

@section('title', 'Upload a past graduates list')

@section('administration-content')
    <x-page-toolbar title="Upload a past graduates list" meta="Bring in a whole cohort at once - nothing is registered until you confirm the review screen">
        <x-slot:actions>
            <a href="{{ route('administration.alumni.index') }}" class="tich-btn tich-btn-ghost">Back to the register</a>
            <a href="{{ route('administration.alumni.template') }}" class="tich-btn tich-btn-secondary">Download the template</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('file')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-mt-4">
        <form method="POST" action="{{ route('administration.alumni.preview') }}" enctype="multipart/form-data" class="tich-form-stack">
            @csrf
            <div class="tich-form-group">
                <label class="tich-label" for="file">Graduates sheet <span class="tich-text--danger">*</span></label>
                <input type="file" id="file" name="file" class="tich-input" accept=".xlsx,.xls,.csv" required>
                <p class="tich-caption tich-mt-2">Excel or CSV, up to 10 MB. A .xls file saved from Google Sheets works too.</p>
            </div>

            <div class="tich-form-group">
                <p class="tich-label">Expected columns</p>
                <p class="tich-caption">
                    <code>registration_number</code>, <code>full_name</code> (or first/middle/surname),
                    <code>graduation_year</code>, <code>graduation_date</code>, <code>graduation_award</code>.
                    Only the admission number or name plus a year or date are needed - extra columns are ignored.
                </p>
            </div>

            <div class="tich-form-group">
                <p class="tich-label">How each line is treated</p>
                <p class="tich-caption">
                    A line is matched to the student the school already has. Names alone are only accepted when
                    exactly one student carries that full name. Anything that cannot be matched gets an
                    <a href="{{ route('administration.alumni.add') }}">Add this graduate</a> button on the review screen,
                    which creates the record with the course, campus and GPA you type in.
                </p>
            </div>

            <div class="tich-form-actions">
                <button type="submit" class="tich-btn tich-btn-primary">Review list</button>
            </div>
        </form>
    </div>
@endsection
