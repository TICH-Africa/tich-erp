@php
    $plannedDate = old('planned_date');
    if (! $plannedDate && ! empty($plan->planned_date)) {
        $plannedDate = $plan->planned_date instanceof \DateTimeInterface
            ? $plan->planned_date->format('Y-m-d')
            : substr((string) $plan->planned_date, 0, 10);
    }
@endphp
<div class="tich-form-group">
    <label class="tich-label">Planned date</label>
    <input type="date" name="planned_date" class="tich-input" value="{{ $plannedDate }}" required>
</div>
<div class="tich-form-group">
    <label class="tich-label">Week number</label>
    <input type="number" name="week_number" class="tich-input" value="{{ old('week_number', $plan->week_number) }}" min="1">
</div>
<div class="tich-form-group">
    <label class="tich-label">Contact hours (this session)</label>
    <input type="number" name="contact_hours" class="tich-input" value="{{ old('contact_hours', $plan->contact_hours) }}" min="1" required>
</div>
<div class="tich-form-group">
    <label class="tich-label">Lesson objectives</label>
    <textarea name="lesson_objectives" class="tich-input" rows="4" required>{{ old('lesson_objectives', $plan->lesson_objectives) }}</textarea>
</div>
<div class="tich-form-group">
    <label class="tich-label">Topics covered</label>
    <textarea name="topics_covered" class="tich-input" rows="3">{{ old('topics_covered', $plan->topics_covered) }}</textarea>
</div>
<div class="tich-form-group">
    <label class="tich-label">Core competencies targeted</label>
    <textarea name="competencies_targeted" class="tich-input" rows="3" placeholder="List competencies from the unit curriculum">{{ old('competencies_targeted', $plan->competencies_targeted) }}</textarea>
</div>
<div class="tich-form-group">
    <label class="tich-label">Teaching methods</label>
    <input type="text" name="teaching_methods" class="tich-input" value="{{ old('teaching_methods', $plan->teaching_methods) }}" placeholder="Lecture, demonstration, group work">
</div>
<div class="tich-form-group">
    <label class="tich-label">Resources required</label>
    <input type="text" name="resources_required" class="tich-input" value="{{ old('resources_required', $plan->resources_required) }}" placeholder="Lab, projector, handouts">
</div>
