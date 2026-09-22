@extends('layouts.marketing')

@section('title', 'Create Report')

@section('department-content')
    <x-page-toolbar title="Create Report" meta="Weekly, quarterly, or annual marketing report" />

    <form method="POST" action="{{ route('marketing.reports.store') }}" class="tich-blog-compose" enctype="multipart/form-data" style="padding: 1rem;">
        @csrf
        <div class="tich-form-grid tich-form-grid--2" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="report_type">Type</label>
                <select id="report_type" name="report_type" class="tich-input" required>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annual">Annual</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="title">Title</label>
                <input id="title" type="text" name="title" class="tich-input" required maxlength="300">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="report_date">Report Date</label>
                <input id="report_date" type="date" name="report_date" class="tich-input" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="prepared_by">Prepared By</label>
                <select id="prepared_by" name="prepared_by" class="tich-input">
                    <option value="">Auto (logged in user)</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}">{{ $person->fullName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="summary">Summary</label>
                <textarea id="summary" name="summary" class="tich-input" rows="3"></textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="commentary">Commentary</label>
                <textarea id="commentary" name="commentary" class="tich-input" rows="5" placeholder="Qualitative commentary about what happened this period"></textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="anomalies">Anomalies</label>
                <textarea id="anomalies" name="anomalies" class="tich-input" rows="3" placeholder="Flag any anomalies or issues"></textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="attachments">Attachments (Optional)</label>
                <input id="attachments" type="file" name="attachments[]" class="tich-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                <p class="tich-caption tich-mt-1">PDF, Word, Excel, or images. Multiple files allowed.</p>
            </div>
        </div>
        <div class="tich-mt-4 tich-blog-compose__footer">
            <a href="{{ route('marketing.reports.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Create Report</button>
        </div>
    </form>
@endsection
