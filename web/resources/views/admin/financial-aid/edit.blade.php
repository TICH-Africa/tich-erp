@extends('layouts.admin')

@section('title', 'Edit ' . $financialAidOpportunity->title)

@section('content')
    <x-page-toolbar title="Edit Financial Aid Opportunity" meta="{{ $financialAidOpportunity->title }}">
        <x-slot:actions>
            <a href="{{ route('admin.financial-aid.show', $financialAidOpportunity) }}" class="tich-btn tich-btn-ghost">View</a>
            <a href="{{ route('admin.financial-aid.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.financial-aid.update', $financialAidOpportunity) }}" class="tich-card tich-mt-6">
        @csrf
        @method('PUT')

        <div class="tich-card__body">
            <div class="uf-form-section">
                <div class="uf-section-head">Basic Information</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Title <span class="uf-req">*</span></label>
                            <input type="text" id="title" name="title" value="{{ old('title', $financialAidOpportunity->title) }}" required maxlength="300">
                        </div>
                        <div class="uf-field">
                            <label for="slug">Slug <span class="uf-req">*</span></label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug', $financialAidOpportunity->slug) }}" required maxlength="300">
                        </div>
                        <div class="uf-field" style="grid-column: 1 / -1;">
                            <label for="description">Description <span class="uf-req">*</span></label>
                            <textarea id="description" name="description" rows="4" required maxlength="5000">{{ old('description', $financialAidOpportunity->description) }}</textarea>
                        </div>
                        <div class="uf-field" style="grid-column: 1 / -1;">
                            <label for="eligibility_criteria">Eligibility Criteria</label>
                            <textarea id="eligibility_criteria" name="eligibility_criteria" rows="4" maxlength="5000">{{ old('eligibility_criteria', $financialAidOpportunity->eligibility_criteria) }}</textarea>
                        </div>
                        <div class="uf-field" style="grid-column: 1 / -1;">
                            <label for="application_process">Application Process</label>
                            <textarea id="application_process" name="application_process" rows="4" maxlength="5000">{{ old('application_process', $financialAidOpportunity->application_process) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Funding Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="funding_type">Funding Type <span class="uf-req">*</span></label>
                            <select id="funding_type" name="funding_type" required>
                                <option value="scholarship" @selected($financialAidOpportunity->funding_type === 'scholarship')>Scholarship</option>
                                <option value="grant" @selected($financialAidOpportunity->funding_type === 'grant')>Grant</option>
                                <option value="loan" @selected($financialAidOpportunity->funding_type === 'loan')>Loan</option>
                                <option value="work_study" @selected($financialAidOpportunity->funding_type === 'work_study')>Work-Study</option>
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="amount">Amount (KES)</label>
                            <input type="number" id="amount" name="amount" value="{{ old('amount', $financialAidOpportunity->amount) }}" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Application Timeline</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="application_open_date">Applications Open Date</label>
                            <input type="date" id="application_open_date" name="application_open_date" value="{{ old('application_open_date', $financialAidOpportunity->application_open_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="uf-field">
                            <label for="application_deadline">Application Deadline</label>
                            <input type="date" id="application_deadline" name="application_deadline" value="{{ old('application_deadline', $financialAidOpportunity->application_deadline?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Status</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="status">Status <span class="uf-req">*</span></label>
                            <select id="status" name="status" required>
                                <option value="draft" @selected($financialAidOpportunity->status === 'draft')>Draft</option>
                                <option value="published" @selected($financialAidOpportunity->status === 'published')>Published</option>
                                <option value="closed" @selected($financialAidOpportunity->status === 'closed')>Closed</option>
                                <option value="archived" @selected($financialAidOpportunity->status === 'archived')>Archived</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tich-flex tich-flex--end tich-gap-3 tich-mt-6 tich-pt-4 tich-border-t">
                <a href="{{ route('admin.financial-aid.show', $financialAidOpportunity) }}" class="tich-btn tich-btn-secondary">Cancel</a>
                <button type="submit" class="tich-btn tich-btn-primary">Update Opportunity</button>
            </div>
        </div>
    </form>
@endsection