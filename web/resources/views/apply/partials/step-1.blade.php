<h2 class="tich-h3">Step 1 — Choose programme</h2>
<p class="tich-text tich-mt-2">Select the programme you wish to apply for and your preferred campus.</p>

        @if ($programs->isEmpty())
        <p class="tich-field-error tich-mt-4">Programme catalogue is not available yet. Run <code>php artisan db:seed --class=ProgramsSeeder</code> or add programmes in the admin panel.</p>
        @endif

        <div class="tich-form-group tich-mt-6">
    <label for="program_id" class="tich-label">Programme</label>
    <select id="program_id" name="program_id" class="tich-input" required>
        <option value="">Select a programme</option>
        @foreach ($programs as $program)
            <option value="{{ $program->id ?? '' }}" @selected(old('program_id', $draft['program_id'] ?? '') == ($program->id ?? ''))>
                {{ $program->program_code }} — {{ $program->program_name }}
            </option>
        @endforeach
    </select>
    @error('program_id')<p class="tich-field-error">{{ $message }}</p>@enderror
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
