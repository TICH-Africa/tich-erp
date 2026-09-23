@extends('layouts.marketing')

@section('title', 'Edit Report')

@section('department-content')
    <x-page-toolbar title="Edit Report" meta="{{ $report->title }}" />

    <form method="POST" action="{{ route('marketing.reports.update', $report) }}" class="tich-blog-compose" enctype="multipart/form-data" style="padding: 1rem;">
        @csrf
        @method('PUT')
        <div class="tich-form-grid tich-form-grid--2" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="report_type">Type</label>
                <select id="report_type" name="report_type" class="tich-input" required>
                    <option value="weekly" {{ old('report_type', $report->report_type) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ old('report_type', $report->report_type) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ old('report_type', $report->report_type) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="annual" {{ old('report_type', $report->report_type) === 'annual' ? 'selected' : '' }}>Annual</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="title">Title</label>
                <input id="title" type="text" name="title" class="tich-input" value="{{ old('title', $report->title) }}" required maxlength="300">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="report_date">Report Date</label>
                <input id="report_date" type="date" name="report_date" class="tich-input" value="{{ old('report_date', $report->report_date->format('Y-m-d')) }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="prepared_by">Prepared By</label>
                <select id="prepared_by" name="prepared_by" class="tich-input">
                    <option value="">Auto (logged in user)</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}" {{ old('prepared_by', (string) $report->prepared_by) == $person->id ? 'selected' : '' }}>{{ $person->fullName() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="summary">Summary</label>
                <textarea id="summary" name="summary" class="tich-input" rows="3">{{ old('summary', $report->summary) }}</textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="commentary">Commentary</label>
                <textarea id="commentary" name="commentary" class="tich-input" rows="5" placeholder="Qualitative commentary about what happened this period">{{ old('commentary', $report->commentary) }}</textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="anomalies">Anomalies</label>
                <textarea id="anomalies" name="anomalies" class="tich-input" rows="3" placeholder="Flag any anomalies or issues">{{ old('anomalies', $report->anomalies) }}</textarea>
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="attachments">Additional Attachments (Optional)</label>
                <input id="attachments" type="file" name="attachments[]" class="tich-input" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                <p class="tich-caption tich-mt-1">PDF, Word, Excel, or images. Multiple files allowed.</p>
            </div>
        </div>
        <div class="tich-mt-4 tich-blog-compose__footer">
            <a href="{{ route('marketing.reports.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Update Report</button>
        </div>
    </form>
@endsection
