@extends('layouts.administration')

@section('title', 'Create Financial Aid Opportunity')

@section('administration-content')
    <x-page-toolbar title="Create Financial Aid Opportunity" meta="Add a new scholarship, grant, or financial aid program">
        <x-slot:actions>
            <a href="{{ route('administration.financial-aid.index') }}" class="tich-btn tich-btn-ghost">Back</a>
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

    <form method="POST" action="{{ route('administration.financial-aid.store') }}" class="tich-card tich-mt-6">
        @csrf

        <div class="tich-card__body">
            <div class="uf-form-section">
                <div class="uf-section-head">Basic Information</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="title">Title <span class="uf-req">*</span></label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="300">
                        </div>
                        <div class="uf-field">
                            <label for="slug">Slug <span class="uf-req">*</span></label>
                            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required maxlength="300" placeholder="e.g., tich-merit-scholarship-2026">
                            <p class="uf-hint tich-mt-1">URL-friendly identifier. Will be auto-generated from title if left blank.</p>
                        </div>
                        <div class="uf-field" style="grid-column: 1 / -1;">
                            <label for="description">Description <span class="uf-req">*</span></label>
                            <textarea id="description" name="description" rows="4" required maxlength="5000">{{ old('description') }}</textarea>
                            <p class="uf-hint tich-mt-1">General description of the opportunity shown on the public page.</p>
                        </div>
                        <div class="uf-field" style="grid-column: 1 / -1;">
                            <label for="eligibility_criteria">Eligibility Criteria</label>
                            <textarea id="eligibility_criteria" name="eligibility_criteria" rows="4" maxlength="5000">{{ old('eligibility_criteria') }}</textarea>
                            <p class="uf-hint tich-mt-1">Who can apply? GPA requirements, program, year of study, etc.</p>
                        </div>
                        <div class="uf-field" style="grid-column: 1 / -1;">
                            <label for="application_process">Application Process</label>
                            <textarea id="application_process" name="application_process" rows="4" maxlength="5000">{{ old('application_process') }}</textarea>
                            <p class="uf-hint tich-mt-1">Step-by-step instructions for applicants.</p>
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
                                <option value="scholarship" @selected(old('funding_type') === 'scholarship')>Scholarship</option>
                                <option value="grant" @selected(old('funding_type') === 'grant')>Grant</option>
                                <option value="loan" @selected(old('funding_type') === 'loan')>Loan</option>
                                <option value="work_study" @selected(old('funding_type') === 'work_study')>Work-Study</option>
                            </select>
                        </div>
                        <div class="uf-field">
                            <label for="amount">Amount (KES)</label>
                            <input type="number" id="amount" name="amount" value="{{ old('amount') }}" step="0.01" min="0" placeholder="0.00">
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
                            <input type="date" id="application_open_date" name="application_open_date" value="{{ old('application_open_date') }}">
                        </div>
                        <div class="uf-field">
                            <label for="application_deadline">Application Deadline</label>
                            <input type="date" id="application_deadline" name="application_deadline" value="{{ old('application_deadline') }}">
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
                                <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                                <option value="published" @selected(old('status') === 'published')>Published</option>
                                <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                                <option value="archived" @selected(old('status') === 'archived')>Archived</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tich-flex tich-flex--end tich-gap-3 tich-mt-6 tich-pt-4 tich-border-t">
                <a href="{{ route('administration.financial-aid.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
                <button type="submit" class="tich-btn tich-btn-primary">Create Opportunity</button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');

            if (titleInput && slugInput) {
                titleInput.addEventListener('blur', function() {
                    if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
                        slugInput.value = generateSlug(this.value);
                        slugInput.dataset.autoGenerated = 'true';
                    }
                });

                slugInput.addEventListener('input', function() {
                    this.dataset.autoGenerated = 'false';
                });
            }

            function generateSlug(text) {
                return text
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }
        });
    </script>
@endsection