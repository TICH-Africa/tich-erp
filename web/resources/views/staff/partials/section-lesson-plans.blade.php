<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Lesson plans</h1>
    <p class="tich-text">Use the standardized template below. Submit for HOD approval before initiating any class session on that date.</p>
</header>

<div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">New lesson plan</h2>
        @if ($portalData['allocations']->isEmpty())
            <p class="tich-text tich-mt-4">No units assigned.</p>
        @else
            <form method="POST" action="{{ route('staff.lesson-plans.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Unit</label>
                    <select name="allocation_id" class="tich-input" required>
                        @foreach ($portalData['allocations'] as $allocation)
                            <option value="{{ $allocation->id }}">{{ $allocation->unit?->unit_code }} · {{ $allocation->intake_label ?? $allocation->semester?->semester_label }}</option>
                        @endforeach
                    </select>
                </div>
                @include('academics.lesson-plans.partials.form-fields', ['plan' => (object) ['planned_date' => now(), 'week_number' => 1, 'contact_hours' => 2, 'lesson_objectives' => '', 'topics_covered' => '', 'competencies_targeted' => '', 'teaching_methods' => '', 'resources_required' => '']])
                <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
                    <button type="submit" class="tich-btn tich-btn-secondary">Save draft</button>
                    <button type="submit" name="submit" value="1" class="tich-btn tich-btn-primary">Submit for HOD approval</button>
                </div>
            </form>
        @endif
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Your lesson plans</h2>
        @if ($portalData['lesson_plans']->isEmpty())
            <p class="tich-text tich-mt-4">No lesson plans yet.</p>
        @else
            <table class="tich-admin-table tich-mt-4">
                <thead><tr><th>Unit</th><th>Date</th><th>Hrs</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($portalData['lesson_plans'] as $plan)
                        <tr>
                            <td>{{ $plan->unit_code }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</td>
                            <td>{{ $plan->contact_hours }}</td>
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
                            </td>
                            <td style="white-space:nowrap;">
                                @if (in_array($plan->status, ['draft', 'modified', 'rejected'], true))
                                    <a href="{{ route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id]) }}" class="tich-link">Edit</a>
                                    @if ($plan->status !== 'draft')
                                        ·
                                    @endif
                                @endif
                                @if (in_array($plan->status, ['draft', 'modified', 'rejected'], true))
                                    <form method="POST" action="{{ route('staff.lesson-plans.submit', $plan->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Submit</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @if ($plan->hod_comments && in_array($plan->status, ['rejected', 'modified'], true))
                            <tr>
                                <td colspan="5" class="tich-caption" style="padding-top:0;">HOD: {{ $plan->hod_comments }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    </article>
</div>

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
        <form method="POST" action="{{ route('staff.lesson-plans.update', $editPlan->id) }}" class="tich-mt-4">
            @csrf
            @method('PUT')
            @include('academics.lesson-plans.partials.form-fields', ['plan' => $editPlan])
            <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-top:1rem;">
                <button type="submit" class="tich-btn tich-btn-secondary">Save changes</button>
            </div>
        </form>
        <form method="POST" action="{{ route('staff.lesson-plans.submit', $editPlan->id) }}" class="tich-mt-4">
            @csrf
            <button type="submit" class="tich-btn tich-btn-primary">Submit for HOD approval</button>
        </form>
    </article>
@endif
