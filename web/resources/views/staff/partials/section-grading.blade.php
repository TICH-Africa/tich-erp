<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Assessment &amp; grading</h1>
    <p class="tich-text">Record CATs, assignments, practicals, and skills lab scores.</p>
</header>

<div class="tich-grid tich-grid--2 tich-mt-6" style="align-items:start; gap:1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Record score</h2>
        @if ($portalData['allocations']->isEmpty())
            <p class="tich-text tich-mt-4">No units assigned.</p>
        @else
            <form method="POST" action="{{ route('staff.grading.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Unit</label>
                    <select name="allocation_id" id="grading-allocation" class="tich-input" required>
                        @foreach ($portalData['allocations'] as $allocation)
                            <option value="{{ $allocation->id }}">{{ $allocation->unit?->unit_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Student</label>
                    <select name="student_id" class="tich-input" required>
                        @foreach ($portalData['allocations'] as $allocation)
                            @foreach (($rostersByAllocation[$allocation->id] ?? collect()) as $student)
                                <option value="{{ $student->student_id }}" data-allocation="{{ $allocation->id }}">
                                    {{ $student->registration_number }} · {{ trim($student->student_name) }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Assessment name</label>
                    <input type="text" name="assessment_name" class="tich-input" required placeholder="CAT 1">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Type</label>
                    <select name="assessment_type" class="tich-input">
                        <option value="cat">CAT</option>
                        <option value="assignment">Assignment</option>
                        <option value="practical">Practical</option>
                        <option value="skills_lab">Skills lab</option>
                    </select>
                </div>
                <div class="tich-grid tich-grid--2" style="gap:1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">Max score</label>
                        <input type="number" step="0.01" name="max_score" class="tich-input" value="30" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Score obtained</label>
                        <input type="number" step="0.01" name="score_obtained" class="tich-input" required>
                    </div>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Weight in final (%)</label>
                    <input type="number" step="0.01" name="weight_in_final" class="tich-input" value="0">
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Save score</button>
            </form>
        @endif
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Recent scores</h2>
        @if ($portalData['cat_scores']->isEmpty())
            <p class="tich-text tich-mt-4">No scores recorded yet.</p>
        @else
            <table class="tich-admin-table tich-mt-4">
                <thead><tr><th>Student</th><th>Unit</th><th>Assessment</th><th>Score</th></tr></thead>
                <tbody>
                    @foreach ($portalData['cat_scores']->take(20) as $score)
                        <tr>
                            <td>{{ trim($score->student_name) ?: $score->registration_number }}</td>
                            <td>{{ $score->unit_code }}</td>
                            <td>{{ $score->assessment_name }}</td>
                            <td>{{ number_format((float) $score->score_obtained, 1) }}/{{ number_format((float) $score->max_score, 1) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </article>
</div>
