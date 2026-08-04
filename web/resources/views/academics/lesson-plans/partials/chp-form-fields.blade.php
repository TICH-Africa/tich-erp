@php
    $payload = is_array($plan->form_payload ?? null) ? $plan->form_payload : [];
    $fieldValue = function (string $key) use ($payload, $plan) {
        return old($key, $payload[$key] ?? '');
    };
    $plannedDate = old('planned_date');
    if (! $plannedDate && ! empty($plan->planned_date)) {
        $plannedDate = $plan->planned_date instanceof \DateTimeInterface
            ? $plan->planned_date->format('Y-m-d')
            : substr((string) $plan->planned_date, 0, 10);
    }

    $sessionRows = old('session_rows');
    if (! is_array($sessionRows)) {
        $sessionRows = $payload['session_rows'] ?? [
            ['time' => '', 'content' => '', 'trainer_activities' => '', 'learner_activities' => '', 'evaluation' => ''],
        ];
    }
    if ($sessionRows === []) {
        $sessionRows = [
            ['time' => '', 'content' => '', 'trainer_activities' => '', 'learner_activities' => '', 'evaluation' => ''],
        ];
    }

    $rowColumns = config('tich-lesson-plans.session_row_columns', []);
@endphp

<div class="tich-lesson-plan-form" data-lesson-plan-form>
    <div class="tich-form-group">
        <label class="tich-label">Lesson topic</label>
        <input type="text" name="lesson_title" class="tich-input" value="{{ old('lesson_title', $plan->lesson_title ?? $plan->topics_covered ?? '') }}" placeholder="e.g. Governance" required>
    </div>

    <div class="tich-form-grid tich-form-grid--2">
        <div class="tich-form-group">
            <label class="tich-label">Planned date</label>
            <input type="date" name="planned_date" class="tich-input" value="{{ $plannedDate }}" data-lesson-plan-date required>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Week number</label>
            <input type="number" name="week_number" class="tich-input" value="{{ old('week_number', $plan->week_number ?? 1) }}" min="1" data-lesson-plan-week>
        </div>
    </div>

    <div class="tich-form-grid tich-form-grid--2">
        <div class="tich-form-group">
            <label class="tich-label">Session time</label>
            <input type="text" name="session_time" class="tich-input" value="{{ $fieldValue('session_time') }}" placeholder="08:00 - 10:00" data-lesson-plan-session-time required>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Class / intake</label>
            <input type="text" name="intake_class" class="tich-input" value="{{ $fieldValue('intake_class') }}" placeholder="CHP intake, group, or cohort" data-lesson-plan-intake required>
        </div>
    </div>

    <div class="tich-form-grid tich-form-grid--2">
        <div class="tich-form-group">
            <label class="tich-label">Venue</label>
            <input type="text" name="venue" class="tich-input" value="{{ $fieldValue('venue') }}" placeholder="Classroom / lab / field site" data-lesson-plan-venue required>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">Contact hours (this session)</label>
            <input type="number" name="contact_hours" class="tich-input" value="{{ old('contact_hours', $plan->contact_hours ?? 2) }}" min="1" data-lesson-plan-hours required>
        </div>
    </div>

    <div class="tich-form-group">
        <label class="tich-label">General objective</label>
        <textarea name="general_objective" class="tich-input" rows="2" required>{{ $fieldValue('general_objective') }}</textarea>
    </div>
    <div class="tich-form-group">
        <label class="tich-label">Specific objectives / learning outcomes</label>
        <textarea name="lesson_objectives" class="tich-input" rows="3" required>{{ old('lesson_objectives', $plan->lesson_objectives ?? '') }}</textarea>
    </div>
    <div class="tich-form-group">
        <label class="tich-label">Key competencies targeted</label>
        <textarea name="competencies_targeted" class="tich-input" rows="2" required>{{ old('competencies_targeted', $plan->competencies_targeted ?? '') }}</textarea>
    </div>
    <div class="tich-form-group">
        <label class="tich-label">Prior knowledge / entry behaviour</label>
        <textarea name="prior_knowledge" class="tich-input" rows="2" required>{{ $fieldValue('prior_knowledge') }}</textarea>
    </div>
    <div class="tich-form-group">
        <label class="tich-label">Resources / materials required</label>
        <input type="text" name="resources_required" class="tich-input" value="{{ old('resources_required', $plan->resources_required ?? '') }}" placeholder="Projector, handouts, flip charts, models" required>
    </div>

    <div class="tich-form-toolbar tich-mt-4">
        <h3 class="tich-h3">Lesson session plan</h3>
        <p class="tich-caption">Add rows for each stage of the session (time, content, trainer and learner activities, evaluation).</p>
    </div>

    <div class="tich-lesson-plan-grid-wrap tich-mt-4">
        <table class="tich-lesson-plan-grid" data-lesson-plan-rows>
            <thead>
                <tr>
                    @foreach ($rowColumns as $columnKey => $columnLabel)
                        <th>{{ $columnLabel }}</th>
                    @endforeach
                    <th class="tich-lesson-plan-grid__actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessionRows as $rowIndex => $row)
                    <tr data-lesson-plan-row>
                        @foreach (array_keys($rowColumns) as $columnKey)
                            <td>
                                <textarea
                                    name="session_rows[{{ $rowIndex }}][{{ $columnKey }}]"
                                    class="tich-lesson-plan-grid__input"
                                    rows="{{ $columnKey === 'content' || str_contains($columnKey, 'activities') ? 4 : 2 }}"
                                    placeholder="{{ $rowColumns[$columnKey] }}"
                                >{{ old("session_rows.$rowIndex.$columnKey", $row[$columnKey] ?? '') }}</textarea>
                            </td>
                        @endforeach
                        <td class="tich-lesson-plan-grid__actions">
                            <button type="button" class="tich-link" data-lesson-plan-remove-row @if (count($sessionRows) <= 1) hidden @endif>Remove</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <button type="button" class="tich-btn tich-btn-secondary tich-mt-4" data-lesson-plan-add-row>Add row</button>

    <div class="tich-form-grid tich-form-grid--2 tich-mt-6">
        <div class="tich-form-group">
            <label class="tich-label">Assignment / homework</label>
            <textarea name="assignment" class="tich-input" rows="2">{{ $fieldValue('assignment') }}</textarea>
        </div>
        <div class="tich-form-group">
            <label class="tich-label">References</label>
            <textarea name="references" class="tich-input" rows="2">{{ $fieldValue('references') }}</textarea>
        </div>
    </div>

    <input type="hidden" name="topics_covered" value="{{ old('topics_covered', $plan->topics_covered ?? '') }}">
    <input type="hidden" name="teaching_methods" value="{{ old('teaching_methods', $plan->teaching_methods ?? '') }}">
</div>

<template id="lesson-plan-row-template">
    <tr data-lesson-plan-row>
        @foreach (array_keys($rowColumns) as $columnKey)
            <td>
                <textarea
                    name="session_rows[__INDEX__][{{ $columnKey }}]"
                    class="tich-lesson-plan-grid__input"
                    rows="{{ $columnKey === 'content' || str_contains($columnKey, 'activities') ? 4 : 2 }}"
                    placeholder="{{ $rowColumns[$columnKey] }}"
                ></textarea>
            </td>
        @endforeach
        <td class="tich-lesson-plan-grid__actions">
            <button type="button" class="tich-link" data-lesson-plan-remove-row>Remove</button>
        </td>
    </tr>
</template>
