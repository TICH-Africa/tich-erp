@extends('layouts.app')

@section('title', 'Apply for ' . $opportunity->title)
@section('meta_description', 'Apply for ' . $opportunity->title . ' financial aid opportunity')

@section('content')
    <header class="tich-course-page-header" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
        <div class="tich-container">
            <h1 class="tich-course-page-header__title">Apply for {{ $opportunity->title }}</h1>
            <p class="tich-course-page-header__lead">Complete the application form below. All fields marked with * are required.</p>
        </div>
    </header>

    <section class="tich-section tich-mt-8">
        <div class="tich-container">
            <div class="tich-grid tich-grid--3" style="gap: 2rem;">
                <article class="tich-card" style="grid-column: span 2;">
                    @if ($errors->any())
                        <div class="tich-alert tich-alert--error tich-mb-6">
                            <ul style="margin:0; padding-left:1.25rem;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('financial-aid.submit', $opportunity->slug) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="uf-form-section">
                            <div class="uf-section-head">Personal Information</div>
                            <div class="uf-section-body">
                                <div class="uf-form-grid-2">
                                    <div class="uf-field">
                                        <label for="student_name">Full Name <span class="uf-req">*</span></label>
                                        <input type="text" id="student_name" name="student_name" value="{{ old('student_name', auth()->user()?->name ?? '') }}" required maxlength="300">
                                    </div>
                                    <div class="uf-field">
                                        <label for="student_email">Email Address <span class="uf-req">*</span></label>
                                        <input type="email" id="student_email" name="student_email" value="{{ old('student_email', auth()->user()?->email ?? '') }}" required maxlength="255">
                                    </div>
                                    <div class="uf-field">
                                        <label for="student_phone">Phone Number</label>
                                        <input type="text" id="student_phone" name="student_phone" value="{{ old('student_phone') }}" maxlength="30" placeholder="07XX XXXXXXX">
                                    </div>
                                    <div class="uf-field">
                                        <label for="student_number">Student/ID Number</label>
                                        <input type="text" id="student_number" name="student_number" value="{{ old('student_number') }}" maxlength="50">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="uf-form-section">
                            <div class="uf-section-head">Academic Information</div>
                            <div class="uf-section-body">
                                <div class="uf-form-grid-2">
                                    <div class="uf-field">
                                        <label for="program_applied">Program Applying For</label>
                                        <input type="text" id="program_applied" name="program_applied" value="{{ old('program_applied') }}" maxlength="300" placeholder="e.g., Diploma in Community Health">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="uf-form-section">
                            <div class="uf-section-head">Personal Statement <span class="uf-req">*</span></div>
                            <div class="uf-section-body">
                                <div class="uf-field">
                                    <label for="personal_statement">Why should you be considered for this financial aid? Describe your academic goals, achievements, and how this support will impact your education.</label>
                                    <textarea id="personal_statement" name="personal_statement" rows="6" required maxlength="2000">{{ old('personal_statement') }}</textarea>
                                    <p class="tich-caption tich-mt-2">Minimum 200 characters. Explain your academic aspirations and how this aid will help you achieve them.</p>
                                </div>
                            </div>
                        </div>

                        <div class="uf-form-section">
                            <div class="uf-section-head">Financial Need Statement <span class="uf-req">*</span></div>
                            <div class="uf-section-body">
                                <div class="uf-field">
                                    <label for="financial_need_statement">Describe your financial situation and need for this support. Include family income, dependents, and any other relevant circumstances.</label>
                                    <textarea id="financial_need_statement" name="financial_need_statement" rows="6" required maxlength="2000">{{ old('financial_need_statement') }}</textarea>
                                    <p class="tich-caption tich-mt-2">Minimum 200 characters. Be specific about your financial challenges.</p>
                                </div>
                            </div>
                        </div>

                        <div class="uf-form-section">
                            <div class="uf-section-head">Supporting Documents</div>
                            <div class="uf-section-body">
                                <div class="uf-field">
                                    <label for="supporting_documents">Upload Supporting Documents</label>
                                    <input type="file" id="supporting_documents" name="supporting_documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <p class="tich-caption tich-mt-2">Optional. Upload academic transcripts, recommendation letters, bank statements, ID copies, etc. (Max 5 files, 5MB each)</p>
                                </div>
                            </div>
                        </div>

                        <div class="uf-form-actions">
                            <button type="submit" class="uf-btn uf-btn-primary">Submit Application</button>
                            <a href="{{ route('financial-aid.show', $opportunity->slug) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        </div>

                        <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
                    </form>
                </article>

                <aside class="tich-card" style="position: sticky; top: 2rem;">
                    <div class="tich-card__body">
                        <div class="tich-alert tich-alert--info">
                            <h4 class="tich-h5 tich-mb-2">Important Notes</h4>
                            <ul class="tich-list tich-mb-0" style="font-size: 0.875rem;">
                                <li>Applications are reviewed on a rolling basis</li>
                                <li>Incomplete applications may be rejected</li>
                                <li>False information will lead to disqualification</li>
                                @if ($opportunity->application_deadline)
                                    <li>Deadline: {{ $opportunity->application_deadline->format('d M Y') }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection