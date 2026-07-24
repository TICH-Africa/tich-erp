<h2 class="tich-h3">Step 1 — Choose programme</h2>
<p class="tich-text tich-mt-2">Select the programme you wish to apply for, your target intake, and your preferred campus.</p>

@php
    $selectedProgramId = old('program_id', $draft['program_id'] ?? '');
    $selectedProgramCode = strtoupper(old('program_code', $draft['program_code'] ?? ''));
    $selectedIntakeYear = old('intake_year', $draft['intake_year'] ?? '');
    $selectedIntakeMonth = old('intake_month', $draft['intake_month'] ?? '');
@endphp

@if ($programs->isEmpty())
    <p class="tich-field-error tich-mt-4">Programme catalogue is not available yet. Run <code>php artisan db:seed --class=ProgramsSeeder</code> or add programmes in the admin panel.</p>
@endif

@if ($selectedProgramCode && $programs->isNotEmpty())
    <p class="tich-caption tich-mt-4">Pre-selected programme: <strong>{{ $selectedProgramCode }}</strong></p>
@endif

<div class="tich-form-group tich-mt-6">
    <label for="program_id" class="tich-label">Programme</label>
    <select id="program_id" name="program_id" class="tich-input" required>
        <option value="">Select a programme</option>
        @foreach ($programs as $program)
            <option
                value="{{ $program->id ?? '' }}"
                @selected(
                    (string) $selectedProgramId === (string) ($program->id ?? '')
                    || ($selectedProgramCode !== '' && $selectedProgramCode === strtoupper($program->program_code ?? ''))
                )
            >
                {{ $program->program_code }} — {{ $program->program_name }}
            </option>
        @endforeach
    </select>
    @error('program_id')<p class="tich-field-error">{{ $message }}</p>@enderror
</div>

<div class="tich-form-group" id="intake-field" style="display:none;">
    <label for="intake_selection" class="tich-label">Target intake</label>
    <select id="intake_selection" class="tich-input">
        <option value="">Select intake</option>
    </select>
    <input type="hidden" id="intake_year" name="intake_year" value="{{ $selectedIntakeYear }}">
    <input type="hidden" id="intake_month" name="intake_month" value="{{ $selectedIntakeMonth }}">
    @error('intake_year')<p class="tich-field-error">{{ $message }}</p>@enderror
    @error('intake_month')<p class="tich-field-error">{{ $message }}</p>@enderror
    <p class="tich-caption tich-mt-2">Choose the intake you plan to join. This must match an open intake for the programme.</p>
</div>

<div class="tich-form-group">
    <label for="preferred_campus_id" class="tich-label">Preferred campus</label>
    <select id="preferred_campus_id" name="preferred_campus_id" class="tich-input">
        <option value="">No preference</option>
        @foreach ($campuses as $campus)
            <option value="{{ $campus->id }}" @selected(old('preferred_campus_id', $draft['preferred_campus_id'] ?? '') == $campus->id)>
                {{ $campus->campus_name }}
            </option>
        @endforeach
    </select>
</div>

@php
    $intakeOptions = [];
    foreach ($programIntakes as $programId => $intakes) {
        $intakeOptions[$programId] = $intakes->map(fn ($intake) => [
            'year' => (int) $intake->intake_year,
            'month' => (int) $intake->intake_month,
            'label' => $intake->intakeLabel(),
        ])->values()->all();
    }
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const intakesByProgram = @json($intakeOptions);
    const programSelect = document.getElementById('program_id');
    const intakeField = document.getElementById('intake-field');
    const intakeSelect = document.getElementById('intake_selection');
    const intakeYearInput = document.getElementById('intake_year');
    const intakeMonthInput = document.getElementById('intake_month');
    const selectedYear = @json((int) $selectedIntakeYear);
    const selectedMonth = @json((int) $selectedIntakeMonth);

    function renderIntakes(programId) {
        intakeSelect.innerHTML = '<option value="">Select intake</option>';
        intakeYearInput.value = '';
        intakeMonthInput.value = '';

        const intakes = intakesByProgram[programId] || [];
        if (!intakes.length) {
            intakeField.style.display = 'none';
            intakeSelect.removeAttribute('required');
            return;
        }

        intakeField.style.display = '';
        intakeSelect.setAttribute('required', 'required');

        intakes.forEach(function (intake) {
            const option = document.createElement('option');
            option.value = intake.year + '-' + intake.month;
            option.textContent = intake.label;
            if (intake.year === selectedYear && intake.month === selectedMonth) {
                option.selected = true;
                intakeYearInput.value = intake.year;
                intakeMonthInput.value = intake.month;
            }
            intakeSelect.appendChild(option);
        });
    }

    intakeSelect.addEventListener('change', function () {
        const parts = intakeSelect.value.split('-');
        intakeYearInput.value = parts[0] || '';
        intakeMonthInput.value = parts[1] || '';
    });

    programSelect.addEventListener('change', function () {
        renderIntakes(programSelect.value);
    });

    if (programSelect.value) {
        renderIntakes(programSelect.value);
    }
});
</script>
