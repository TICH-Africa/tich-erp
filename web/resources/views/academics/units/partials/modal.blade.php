<div class="tich-modal" id="{{ $modalId }}" hidden>
    <div class="tich-modal__backdrop" data-close-modal></div>
    <div class="tich-modal__dialog">
        <h2 class="tich-h3">{{ $unit ? 'Edit unit' : 'New unit' }}</h2>
        <form method="POST" action="{{ $unit ? route('departments.academics.units.update', array_merge($hub, ['unit' => $unit->id])) : route('departments.academics.units.store', $hub) }}" class="tich-mt-4">
            @csrf
            @if ($unit)
                @method('PUT')
            @endif
            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label">Department</label>
                    <select name="department_id" class="tich-input" required @disabled($unit)>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $unit?->department_id) == $department->id)>{{ $department->dept_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Unit code</label>
                    <input type="text" name="unit_code" class="tich-input" value="{{ old('unit_code', $unit?->unit_code) }}" required maxlength="30">
                </div>
                <div class="tich-form-group" style="grid-column: span 2;">
                    <label class="tich-label">Unit name</label>
                    <input type="text" name="unit_name" class="tich-input" value="{{ old('unit_name', $unit?->unit_name) }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Contact hours</label>
                    <input type="number" name="contact_hours" class="tich-input" min="0" value="{{ old('contact_hours', $unit?->contact_hours ?? 0) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Total learning hours</label>
                    <input type="number" name="total_learning_hours" class="tich-input" min="0" value="{{ old('total_learning_hours', $unit?->total_learning_hours ?? 0) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Priority / order</label>
                    <input type="number" name="display_priority" class="tich-input" min="0" value="{{ old('display_priority', $unit?->display_priority ?? 0) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Credit hours</label>
                    <input type="number" step="0.01" name="credit_hours" class="tich-input" min="0" value="{{ old('credit_hours', $unit?->credit_hours ?? 0) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Semester</label>
                    <input type="number" name="semester" class="tich-input" min="1" value="{{ old('semester', $unit?->semester ?? 1) }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label"><input type="checkbox" name="is_core" value="1" @checked(old('is_core', $unit?->is_core ?? true))> Core unit</label>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label"><input type="checkbox" name="is_practical" value="1" @checked(old('is_practical', $unit?->is_practical ?? false))> Practical</label>
                </div>
            </div>
            <div class="tich-form-group tich-mt-4">
                <label class="tich-label">Description</label>
                <textarea name="description" class="tich-input" rows="3">{{ old('description', $unit?->description) }}</textarea>
            </div>
            <div class="tich-mt-6" style="display:flex; gap:1rem;">
                <button type="submit" class="tich-btn tich-btn-primary">Save</button>
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal>Cancel</button>
            </div>
        </form>
    </div>
</div>
