@php
    $corrections = $studentCorrections;
    $recordStudent = $expandedStudentRecord['student'];
    $actionBase = array_merge($hub, ['program' => $program->id, 'student' => $recordStudent->id]);
    $keepFilters = array_filter([
        'learning_department' => request()->integer('learning_department') ?: null,
        'enrollment_status' => request()->string('enrollment_status')->toString() ?: null,
        'entry_pathway' => request()->string('entry_pathway')->toString() ?: null,
        'admission_year' => request()->integer('admission_year') ?: null,
        'student_search' => trim(request()->string('student_search')->toString()) ?: null,
    ]);
@endphp

<article class="tich-card tich-mt-6" id="unit-corrections">
    <div class="tich-dept-panel__head">
        <h3 class="tich-h3">Unit registration - {{ $recordStudent->registration_number }}</h3>
        @if ($corrections['year'])
            <p class="tich-text" style="margin:0;">
                Working out {{ $recordStudent->displayName() }}'s own year:
                <strong>{{ $corrections['year']['period_label'] }}</strong>
                ({{ $corrections['year']['basis'] }}).
                @if (! $corrections['semesterId'])
                    This intake has no calendar semester for that period yet, so nothing can be added - set the period dates on the Semesters tab.
                @endif
            </p>
        @else
            <p class="tich-text" style="margin:0;">
                This programme is timetabled by nursing block, so units are registered from the intake desk rather than by year of study.
                Close this record to use that desk, and pin the block on the student's SIS record.
            </p>
        @endif
    </div>

    <div class="tich-mt-4">
        <h4 class="tich-h4">Units on this student</h4>
        @if ($corrections['registered']->isEmpty())
            <p class="tich-caption">No units are registered to this student yet.</p>
        @else
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Registered semester</th>
                        <th>Marks</th>
                        <th class="tich-text-right">Un-assign</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($corrections['registered'] as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['unit_code'] }}</strong> · {{ $row['unit_name'] }}
                                @if ($row['outside_their_year'])
                                    <span class="tich-badge" style="margin-left:0.35rem;">not their {{ $corrections['year']['period_label'] ?? 'period' }}</span>
                                @endif
                            </td>
                            <td>{{ $row['semester_label'] }}</td>
                            <td>
                                @if ($row['grade'])
                                    <span class="tich-badge">graded {{ $row['grade'] }}</span>
                                @elseif ($row['has_marks'])
                                    <span class="tich-badge">CAT entered</span>
                                @else
                                    <span class="tich-caption">none yet</span>
                                @endif
                            </td>
                            <td class="tich-text-right">
                                @if ($row['has_marks'])
                                    <span class="tich-caption">clear the marks first</span>
                                @else
                                    <details>
                                        <summary class="tich-link" style="cursor:pointer;">Un-assign</summary>
                                        <form method="POST" action="{{ route('departments.academics.programs.student-units.remove', $actionBase) }}" class="tich-mt-2" style="min-width:18rem; text-align:left;">
                                            @csrf
                                            @foreach ($keepFilters as $key => $value)
                                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                            @endforeach
                                            <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">
                                            <input type="hidden" name="registered_unit_id" value="{{ $row['id'] }}">
                                            <div class="tich-form-group" style="margin:0;">
                                                <label class="tich-label">Why is {{ $row['unit_code'] }} being removed?</label>
                                                <input type="text" name="reason" class="tich-input" required minlength="8" maxlength="500"
                                                    placeholder="e.g. registered on the wrong intake">
                                            </div>
                                            <label class="tich-label tich-mt-2">
                                                <input type="checkbox" name="confirm_removal" value="1" class="tich-checkbox" required>
                                                I confirm this student should no longer be registered on the unit.
                                            </label>
                                            <button type="submit" class="tich-btn tich-btn-danger tich-mt-2">Remove unit</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($corrections['year'] && $corrections['semesterId'])
        <div class="tich-mt-6">
            <h4 class="tich-h4">Units their year is due for but does not hold</h4>
            @if ($corrections['missing']->isEmpty())
                <p class="tich-caption">This student already holds every unit mapped to their own teaching period.</p>
            @else
                <form method="POST" action="{{ route('departments.academics.programs.student-units.add', $actionBase) }}" class="tich-flex-wrap" style="gap:0.75rem; align-items:flex-end;">
                    @csrf
                    @foreach ($keepFilters as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">

                    <div class="tich-form-group" style="margin:0; min-width:20rem;">
                        <label class="tich-label" for="unit_id">Add a unit</label>
                        <select id="unit_id" name="unit_id" class="tich-input" required>
                            @foreach ($corrections['missing'] as $unit)
                                <option value="{{ $unit['unit_id'] }}">{{ $unit['unit_code'] }} · {{ $unit['unit_name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="tich-btn tich-btn-primary">Add to this student</button>
                    @error('unit_id')
                        <p class="tich-field-error">{{ $message }}</p>
                    @enderror
                </form>
            @endif
        </div>

        <div class="tich-mt-6">
            <h4 class="tich-h4">Correct the year and semester of study</h4>
            <p class="tich-caption">
                Use this when the admission arithmetic is wrong for this student alone, for example after a deferment or a transfer.
                Pinning the year and semester changes which units the "Register each student on their own year" button gives them,
                and which timetable they see.
            </p>
            <form method="POST" action="{{ route('departments.academics.programs.student-units.year', $actionBase) }}" class="tich-flex-wrap" style="gap:0.75rem; align-items:flex-end;">
                @csrf
                @foreach ($keepFilters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="intake" value="{{ $selectedIntake->id }}">

                <div class="tich-form-group" style="margin:0; min-width:10rem;">
                    <label class="tich-label" for="current_year">Year of study</label>
                    <select id="current_year" name="current_year" class="tich-input" required>
                        @for ($year = 1; $year <= $corrections['maxYear']; $year++)
                            <option value="{{ $year }}" @selected(($corrections['year']['year'] ?? null) === $year)>Year {{ $year }}</option>
                        @endfor
                    </select>
                </div>

                <div class="tich-form-group" style="margin:0; min-width:11rem;">
                    <label class="tich-label" for="current_term">Semester in that year</label>
                    <select id="current_term" name="current_term" class="tich-input" required>
                        @for ($term = 1; $term <= ($corrections['year']['terms'] ?? 1); $term++)
                            <option value="{{ $term }}" @selected(($corrections['year']['term'] ?? null) === $term)>Semester {{ $term }}</option>
                        @endfor
                    </select>
                </div>

                <div class="tich-form-group" style="margin:0; min-width:18rem; flex:1;">
                    <label class="tich-label" for="year_reason">Why does this student sit that year and semester?</label>
                    <input id="year_reason" type="text" name="reason" class="tich-input" required minlength="8" maxlength="500"
                        placeholder="e.g. deferred a year, admitted 2025">
                </div>

                <button type="submit" class="tich-btn tich-btn-secondary">Save year and semester</button>
                @error('current_year')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
                @error('current_term')
                    <p class="tich-field-error">{{ $message }}</p>
                @enderror
            </form>
        </div>
    @endif
</article>
