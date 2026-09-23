@php
    $workplan = $workplan ?? null;
@endphp
<div class="tich-grid tich-grid--2" style="align-items:start; gap:1.5rem;">
    <div>
        <label class="tich-label" for="title">Title</label>
        <input id="title" type="text" name="title" class="tich-input" required maxlength="300"
               value="{{ old('title', $workplan->title ?? '') }}">
        @error('title')<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="tich-label" for="semester_id">Semester</label>
        <select id="semester_id" name="semester_id" class="tich-input" required>
            <option value="">Select semester</option>
            @foreach ($semesters as $semester)
                <option value="{{ $semester->id }}" @selected((int) old('semester_id', $workplan->semester_id ?? 0) === (int) $semester->id)>
                    {{ $semester->displayLabel() }}
                    @if ($semester->academicYear)
                        · {{ $semester->academicYear->year_label ?? $semester->academicYear->academic_year ?? '' }}
                    @endif
                </option>
            @endforeach
        </select>
        @error('semester_id')<p class="tich-field-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="tich-mt-4">
    <label class="tich-label" for="objectives">Objectives</label>
    <textarea id="objectives" name="objectives" class="tich-input" rows="4">{{ old('objectives', $workplan->objectives ?? '') }}</textarea>
</div>

<div class="tich-grid tich-grid--2 tich-mt-4" style="align-items:start; gap:1.5rem;">
    <div>
        <label class="tich-label" for="resources">Resources</label>
        <textarea id="resources" name="resources" class="tich-input" rows="3">{{ old('resources', $workplan->resources ?? '') }}</textarea>
    </div>
    <div>
        <label class="tich-label" for="kpis">KPIs</label>
        <textarea id="kpis" name="kpis" class="tich-input" rows="3">{{ old('kpis', $workplan->kpis ?? '') }}</textarea>
    </div>
</div>

@include('staff.workplans.partials.activity-rows', ['activities' => $activities ?? []])
