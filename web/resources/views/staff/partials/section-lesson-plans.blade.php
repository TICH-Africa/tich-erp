<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Lesson plans</h1>
    <p class="tich-text">Create and submit lesson plans for HOD approval before class.</p>
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
                            <option value="{{ $allocation->id }}">{{ $allocation->unit?->unit_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Planned date</label>
                    <input type="date" name="planned_date" class="tich-input" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Week number</label>
                    <input type="number" name="week_number" class="tich-input" value="1" min="1">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Lesson objectives</label>
                    <textarea name="lesson_objectives" class="tich-input" rows="4" required></textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Topics covered</label>
                    <textarea name="topics_covered" class="tich-input" rows="3"></textarea>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Teaching methods</label>
                    <input type="text" name="teaching_methods" class="tich-input" placeholder="Lecture, demo, group work">
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="submit" class="tich-btn tich-btn-secondary">Save draft</button>
                    <button type="submit" name="submit" value="1" class="tich-btn tich-btn-primary">Submit for approval</button>
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
                <thead><tr><th>Unit</th><th>Date</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($portalData['lesson_plans'] as $plan)
                        <tr>
                            <td>{{ $plan->unit_code }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</td>
                            <td>{{ ucfirst($plan->status) }}</td>
                            <td>
                                @if ($plan->status === 'draft')
                                    <form method="POST" action="{{ route('staff.lesson-plans.submit', $plan->id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Submit</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </article>
</div>
