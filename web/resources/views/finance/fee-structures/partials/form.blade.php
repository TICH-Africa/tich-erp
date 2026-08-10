@php($feeStructure = $feeStructure ?? null)

<div class="tich-form-row">
    <label class="tich-label" for="program_id">Programme</label>
    <select id="program_id" name="program_id" class="tich-input" required>
        @foreach ($programs as $program)
            <option value="{{ $program->id }}" @selected(old('program_id', $feeStructure?->program_id) == $program->id)>{{ $program->program_name }}</option>
        @endforeach
    </select>
</div>
<div class="tich-form-row">
    <label class="tich-label" for="academic_year_id">Academic year</label>
    <select id="academic_year_id" name="academic_year_id" class="tich-input" required>
        @foreach ($academicYears as $year)
            <option value="{{ $year->id }}" @selected(old('academic_year_id', $feeStructure?->academic_year_id) == $year->id)>{{ $year->year_label }}</option>
        @endforeach
    </select>
</div>
<div class="tich-form-row">
    <label class="tich-label" for="semester_number">Semester</label>
    <input type="number" min="1" max="12" id="semester_number" name="semester_number" class="tich-input" value="{{ old('semester_number', $feeStructure?->semester_number ?? 1) }}" required>
</div>
<div class="tich-form-row">
    <label class="tich-label" for="effective_from">Effective from</label>
    <input type="date" id="effective_from" name="effective_from" class="tich-input" value="{{ old('effective_from', optional($feeStructure?->effective_from)->format('Y-m-d') ?? now()->toDateString()) }}" required>
</div>
@foreach ([
    'tuition_fee' => 'Tuition fee',
    'registration_fee' => 'Registration fee',
    'examination_fee' => 'Examination fee',
    'library_fee' => 'Library fee',
    'activity_fee' => 'Activity fee',
    'hostel_fee' => 'Hostel fee',
    'medical_insurance_fee' => 'Medical insurance',
    'nursing_clinical_fee' => 'Clinical practicum',
    'graduation_fee' => 'Graduation fee',
] as $field => $label)
    <div class="tich-form-row">
        <label class="tich-label" for="{{ $field }}">{{ $label }}</label>
        <input type="number" step="0.01" min="0" id="{{ $field }}" name="{{ $field }}" class="tich-input" value="{{ old($field, $feeStructure?->{$field} ?? 0) }}" @if($field === 'tuition_fee') required @endif>
    </div>
@endforeach
