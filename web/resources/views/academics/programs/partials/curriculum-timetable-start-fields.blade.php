<div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;" class="tich-mt-4">
    <div class="tich-form-group" style="margin:0; max-width:20rem; flex:1 1 14rem;">
        <label class="tich-label">Timetable title</label>
        <input type="text" name="title" class="tich-input" value="{{ old('title') }}" placeholder="{{ $timetableKinds[$timetableKind] ?? 'Timetable' }} - {{ $program->periodLabel($timetableTeachingPeriod) }}" maxlength="200">
    </div>
    <div class="tich-form-group" style="margin:0; max-width:20rem; flex:1 1 14rem;">
        <label class="tich-label">Primary audience (optional)</label>
        <select name="delivery_mode" class="tich-input">
            @foreach (\App\Models\ProgramTimetable::deliveryModes() as $modeKey => $modeLabel)
                <option value="{{ $modeKey }}" @selected(old('delivery_mode', $currentAudience ?? 'physical') === $modeKey)>{{ $modeLabel }}</option>
            @endforeach
        </select>
        <p class="tich-caption tich-mt-1">One timetable per semester — online and physical classes can still be mixed in the grid.</p>
    </div>
</div>
