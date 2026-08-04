<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Lesson plans</h1>
    <p class="tich-text">Prepare your CHP lesson plan online or upload your own document. Submitted plans are sent to your HOD, the Academic Registrar, and the QA Officer.</p>
</header>

@if ($portalData['allocations']->isEmpty())
    <article class="tich-card tich-mt-6">
        <p class="tich-text">No units assigned.</p>
    </article>
@else
    <div class="tich-tabs tich-mt-6" data-tabs="lesson-plan-create">
        <div class="tich-tabs__list" role="tablist">
            <button type="button" class="tich-tabs__tab is-active" data-tab="online">Fill in online</button>
            <button type="button" class="tich-tabs__tab" data-tab="upload">Upload document</button>
        </div>

        <div class="tich-tabs__panel is-active" data-panel="online">
            <article class="tich-card">
                <h2 class="tich-h3">CHP lesson plan template</h2>
                <p class="tich-caption tich-mt-2">Complete the fields below. Preview the generated document, verify it, then submit for approval.</p>

                <form method="POST" action="{{ route('staff.lesson-plans.store') }}" class="tich-mt-4" data-context-url="{{ route('staff.lesson-plans.context') }}" data-autofill-context="1">
                    @csrf
                    <input type="hidden" name="source_type" value="form">
                    <div class="tich-form-group">
                        <label class="tich-label">Unit</label>
                        <select name="allocation_id" class="tich-input" data-lesson-plan-allocation required>
                            @foreach ($portalData['allocations'] as $allocation)
                                <option value="{{ $allocation->id }}" @selected(old('allocation_id') == $allocation->id)>
                                    {{ $allocation->unit?->unit_code }} · {{ $allocation->intake_label ?? $allocation->semester?->semester_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @include('academics.lesson-plans.partials.chp-form-fields', ['plan' => (object) ['planned_date' => now(), 'week_number' => 1, 'contact_hours' => 2, 'lesson_objectives' => '', 'competencies_targeted' => '', 'teaching_methods' => '', 'resources_required' => '', 'form_payload' => []]])
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
                        <button type="submit" class="tich-btn tich-btn-secondary">Save draft</button>
                    </div>
                </form>
                <script src="{{ asset('js/tich-lesson-plan-form.js') }}"></script>
            </article>
        </div>

        <div class="tich-tabs__panel" data-panel="upload">
            <article class="tich-card">
                <h2 class="tich-h3">Upload your lesson plan</h2>
                <p class="tich-caption tich-mt-2">Upload a completed Word or PDF lesson plan. You can submit immediately after upload.</p>

                <form method="POST" action="{{ route('staff.lesson-plans.store') }}" enctype="multipart/form-data" class="tich-mt-4">
                    @csrf
                    <input type="hidden" name="source_type" value="upload">
                    <div class="tich-form-group">
                        <label class="tich-label">Unit</label>
                        <select name="allocation_id" class="tich-input" required>
                            @foreach ($portalData['allocations'] as $allocation)
                                <option value="{{ $allocation->id }}">{{ $allocation->unit?->unit_code }} · {{ $allocation->intake_label ?? $allocation->semester?->semester_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Lesson topic</label>
                        <input type="text" name="lesson_title" class="tich-input" value="{{ old('lesson_title') }}" placeholder="e.g. Governance" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Planned date</label>
                        <input type="date" name="planned_date" class="tich-input" value="{{ old('planned_date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Week number</label>
                        <input type="number" name="week_number" class="tich-input" value="{{ old('week_number', 1) }}" min="1">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Contact hours</label>
                        <input type="number" name="contact_hours" class="tich-input" value="{{ old('contact_hours', 2) }}" min="1" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Lesson plan document</label>
                        <input type="file" name="document" class="tich-input" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                        <p class="tich-caption tich-mt-2">PDF or Word (.pdf, .doc, .docx), up to 10 MB.</p>
                    </div>
                    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
                        <button type="submit" class="tich-btn tich-btn-secondary">Save draft</button>
                        <button type="submit" name="submit" value="1" class="tich-btn tich-btn-primary">Save &amp; submit</button>
                    </div>
                </form>
            </article>
        </div>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Your lesson plans</h2>
        @if ($portalData['lesson_plans']->isEmpty())
            <p class="tich-text tich-mt-4">No lesson plans yet.</p>
        @else
            <table class="tich-admin-table tich-mt-4">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Topic</th>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($portalData['lesson_plans'] as $plan)
                        <tr>
                            <td>{{ $plan->unit_code }}</td>
                            <td>{{ $plan->lesson_title ?: ($plan->topics_covered ?: '-') }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</td>
                            <td>{{ ($plan->source_type ?? 'form') === 'upload' ? 'Upload' : 'Online' }}</td>
                            <td>
                                @php
                                    $statusClass = match ($plan->status) {
                                        'approved' => 'green',
                                        'submitted' => 'amber',
                                        'rejected', 'modified' => 'red',
                                        default => 'neutral',
                                    };
                                @endphp
                                <span class="tich-attendance-flag tich-attendance-flag--{{ $statusClass }}">{{ ucfirst($plan->status) }}</span>
                                @if (($plan->source_type ?? 'form') === 'form' && ! $plan->tutor_verified_at && in_array($plan->status, ['draft', 'modified', 'rejected'], true))
                                    <br><span class="tich-caption">Not verified</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                @if (($plan->source_type ?? 'form') === 'form')
                                    <a href="{{ route('lesson-plans.print', $plan->id) }}" class="tich-link" target="_blank" rel="noopener">Preview</a>
                                @elseif ($plan->uploaded_file_path)
                                    <a href="{{ route('lesson-plans.upload.show', $plan->id) }}" class="tich-link" target="_blank" rel="noopener">Open</a>
                                @endif
                                @if (in_array($plan->status, ['draft', 'modified', 'rejected'], true))
                                    · <a href="{{ route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id]) }}" class="tich-link">Edit</a>
                                @endif
                            </td>
                        </tr>
                        @if ($plan->hod_comments && in_array($plan->status, ['rejected', 'modified'], true))
                            <tr>
                                <td colspan="6" class="tich-caption" style="padding-top:0;">HOD: {{ $plan->hod_comments }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    </article>
@endif

@php
    $editPlanId = request()->integer('edit_plan');
    $editPlan = $editPlanId ? $portalData['lesson_plans']->firstWhere('id', $editPlanId) : null;
@endphp

@if ($editPlan && in_array($editPlan->status, ['draft', 'modified', 'rejected'], true))
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Edit {{ $editPlan->plan_number }}</h2>
        @if ($editPlan->hod_comments)
            <p class="tich-caption tich-mt-2">HOD feedback: {{ $editPlan->hod_comments }}</p>
        @endif

        @if (($editPlan->source_type ?? 'form') === 'upload')
            <form method="POST" action="{{ route('staff.lesson-plans.update', $editPlan->id) }}" enctype="multipart/form-data" class="tich-mt-4">
                @csrf
                @method('PUT')
                <div class="tich-form-group">
                    <label class="tich-label">Lesson topic</label>
                    <input type="text" name="lesson_title" class="tich-input" value="{{ old('lesson_title', $editPlan->lesson_title) }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Planned date</label>
                    <input type="date" name="planned_date" class="tich-input" value="{{ old('planned_date', \Illuminate\Support\Carbon::parse($editPlan->planned_date)->format('Y-m-d')) }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Week number</label>
                    <input type="number" name="week_number" class="tich-input" value="{{ old('week_number', $editPlan->week_number) }}" min="1">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Contact hours</label>
                    <input type="number" name="contact_hours" class="tich-input" value="{{ old('contact_hours', $editPlan->contact_hours) }}" min="1" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Replace document (optional)</label>
                    <input type="file" name="document" class="tich-input" accept=".pdf,.doc,.docx">
                    @if ($editPlan->uploaded_file_name)
                        <p class="tich-caption tich-mt-2">Current file: {{ $editPlan->uploaded_file_name }}</p>
                    @endif
                </div>
                <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Save changes</button>
            </form>
            @if ($editPlan->uploaded_file_path)
                <p class="tich-mt-4">
                    <a href="{{ route('lesson-plans.upload.show', $editPlan->id) }}" class="tich-link" target="_blank" rel="noopener">Open uploaded document</a>
                    ·
                    <a href="{{ route('lesson-plans.upload.download', $editPlan->id) }}" class="tich-link">Download</a>
                </p>
            @endif
            <form method="POST" action="{{ route('staff.lesson-plans.submit', $editPlan->id) }}" class="tich-mt-4">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Submit for approval</button>
            </form>
        @else
            <form method="POST" action="{{ route('staff.lesson-plans.update', $editPlan->id) }}" class="tich-mt-4" data-context-url="{{ route('staff.lesson-plans.context') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="allocation_id" value="{{ $editPlan->unit_allocation_id }}" data-lesson-plan-allocation>
                @include('academics.lesson-plans.partials.chp-form-fields', ['plan' => $editPlan])
                <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Save changes</button>
            </form>
            <script src="{{ asset('js/tich-lesson-plan-form.js') }}"></script>

            <div class="tich-card tich-lesson-plan-verify tich-mt-4">
                <h3 class="tich-h3">Verify before submission</h3>
                <p class="tich-text tich-mt-2">Preview the generated lesson plan document and confirm it is accurate. Submission is only allowed after verification.</p>
                <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
                    <a href="{{ route('lesson-plans.print', $editPlan->id) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">Preview document</a>
                    <a href="{{ route('lesson-plans.pdf', $editPlan->id) }}" class="tich-btn tich-btn-secondary">Download PDF</a>
                    @if (! $editPlan->tutor_verified_at)
                        <form method="POST" action="{{ route('staff.lesson-plans.verify', $editPlan->id) }}">
                            @csrf
                            <button type="submit" class="tich-btn tich-btn-primary">I verify this document is accurate</button>
                        </form>
                    @else
                        <p class="tich-caption" style="align-self:center;">Verified {{ \Illuminate\Support\Carbon::parse($editPlan->tutor_verified_at)->format('d M Y H:i') }}</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('staff.lesson-plans.submit', $editPlan->id) }}" class="tich-mt-4">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary" @disabled(! $editPlan->tutor_verified_at)>Submit for approval</button>
                @if (! $editPlan->tutor_verified_at)
                    <p class="tich-caption tich-mt-2">Verify the document above before submitting to HOD, Academic Registrar, and QA Officer.</p>
                @endif
            </form>
        @endif
    </article>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-tabs="lesson-plan-create"]').forEach(function (tabs) {
            tabs.querySelectorAll('[data-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var name = btn.getAttribute('data-tab');
                    tabs.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.remove('is-active'); });
                    tabs.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    tabs.querySelector('[data-panel="' + name + '"]').classList.add('is-active');
                });
            });
        });
    });
    </script>
@endif
