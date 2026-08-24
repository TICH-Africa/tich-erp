<div class="tich-modal" id="{{ $modalId }}" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="tich-modal__backdrop" data-close-modal="{{ $modalId }}"></div>
    <div class="tich-modal__dialog">
        <header class="tich-modal__header">
            <h2 class="tich-h3 mb-0">{{ $unit ? 'Edit unit' : 'New unit' }}</h2>
            <button type="button" class="tich-modal__close" data-close-modal="{{ $modalId }}" aria-label="Close">&times;</button>
        </header>

        <form method="POST" action="{{ $unit ? route('departments.academics.units.update', array_merge($hub, ['unit' => $unit->id])) : route('departments.academics.units.store', $hub) }}" class="tich-modal__body">
            @csrf
            @if ($unit)
                @method('PUT')
            @endif

            @if (! empty($curriculumReturn))
                @foreach ($curriculumReturn as $name => $value)
                    @if ($value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
            @endif

            @if (! empty($defaultDepartmentId))
                <input type="hidden" name="department_id" value="{{ $defaultDepartmentId }}">
            @endif

            <div class="tich-grid" style="gap: 1rem;">
                @if (empty($defaultDepartmentId))
                    <div class="tich-form-group">
                        <label class="tich-label">Department</label>
                        <select name="department_id" class="tich-input" required @disabled($unit)>
                            <option value="">Select department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id', $unit?->department_id) == $department->id)>{{ $department->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">Unit code</label>
                        <input type="text" name="unit_code" class="tich-input" value="{{ old('unit_code', $unit?->unit_code) }}" required maxlength="30" placeholder="e.g. HSC 101">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Contact hours</label>
                        <input type="number" name="contact_hours" class="tich-input" min="0" value="{{ old('contact_hours', $unit?->contact_hours ?? 0) }}" placeholder="0">
                    </div>
                </div>

                <div class="tich-form-group">
                    <label class="tich-label">Unit name</label>
                    <input type="text" name="unit_name" class="tich-input" value="{{ old('unit_name', $unit?->unit_name) }}" required placeholder="e.g. Introduction to Anatomy">
                </div>

                <div class="tich-grid tich-grid--2" style="gap: 1rem;">
                    <div class="tich-form-group">
                        <label class="tich-label">Total learning hours</label>
                        <input type="number" name="total_learning_hours" class="tich-input" min="0" value="{{ old('total_learning_hours', $unit?->total_learning_hours ?? 0) }}" placeholder="0">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Type</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.25rem; align-items: center;">
                            <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                                <input type="checkbox" name="is_core" value="1" class="tich-checkbox" @checked(old('is_core', $unit?->is_core ?? true))>
                                <span>Core unit</span>
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                                <input type="checkbox" name="is_practical" value="1" class="tich-checkbox" @checked(old('is_practical', $unit?->is_practical ?? false))>
                                <span>Practical</span>
                            </label>
                        </div>
                    </div>
                </div>

                @if (! $unit && ! empty($selectedIntake) && $selectedIntake->status === 'draft' && ! empty($periods) && $periods->isNotEmpty())
                    <div class="tich-form-group">
                        <label class="tich-label">Assign to semester after creating (optional)</label>
                        <select name="assign_semester" class="tich-input">
                            <option value="">Don't assign yet</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period['semester'] ?? 1 }}" @selected((int) old('assign_semester') === (int) ($period['semester'] ?? 1))>{{ $period['label'] }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="assign_intake" value="{{ $selectedIntake->id }}">
                    </div>
                @endif

                <div class="tich-form-group">
                    <label class="tich-label">Description</label>
                    <textarea name="description" class="tich-input" rows="3" placeholder="Brief description of the unit...">{{ old('description', $unit?->description) }}</textarea>
                </div>
            </div>

            <footer class="tich-modal__footer">
                <button type="submit" class="tich-btn tich-btn-primary">{{ $unit ? 'Save changes' : 'Create unit' }}</button>
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="{{ $modalId }}">Cancel</button>
            </footer>
        </form>
    </div>
</div>
