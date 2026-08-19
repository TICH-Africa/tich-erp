@if ($academics['current_period'])
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">
                Semester {{ $academics['current_period']->semester }}
                @if ($academics['current_period_status'] === 'in_progress')
                    <span class="tich-caption">· In progress</span>
                @elseif ($academics['current_period_status'] === 'upcoming')
                    <span class="tich-caption">· Upcoming</span>
                @elseif ($academics['current_period_status'] === 'completed')
                    <span class="tich-caption">· Completed</span>
                @endif
            </h2>
            <p class="tich-text">
                @if ($academics['current_period']->scheduleLabel())
                    {{ $academics['current_period']->scheduleLabel() }}
                @else
                    Semester dates have not been published yet.
                @endif
            </p>
        </div>

        @if ($academics['current_period_units']->isNotEmpty())
            <div class="tich-card tich-table-panel tich-mt-4">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Contact hrs</th>
                            <th>Learning hrs</th>
                            <th>Core</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($academics['current_period_units'] as $mapping)
                            <tr>
                                <td>{{ $mapping->unit?->unit_code }} - {{ $mapping->unit?->unit_name }}</td>
                                <td>{{ $mapping->contact_hours ?? 0 }}</td>
                                <td>{{ $mapping->total_learning_hours ?? 0 }}</td>
                                <td>{{ $mapping->is_compulsory ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            @include('partials.states.empty', ['title' => 'No units are mapped to this semester yet', 'icon' => 'inbox', 'inline' => true])
        @endif
    </section>
@endif

@if ($academics['registered_units']->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">My registered units</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Semester</th>
                        <th>Registered</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($academics['registered_units'] as $row)
                        <tr>
                            <td>{{ $row->unit_code }} - {{ $row->unit_name }}</td>
                            <td>{{ $row->semester_label ?? ('Semester '.$row->semester_number) }}</td>
                            <td>{{ $row->registration_date ? \Illuminate\Support\Carbon::parse($row->registration_date)->format('d M Y') : '-' }}</td>
                            <td>{{ ucfirst($row->registration_status ?? 'registered') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

@if ($otherSemesters->isNotEmpty())
    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Other semesters</h2>
            <p class="tich-text">
                @if ($academics['curriculum'])
                    Full programme plan for {{ $academics['curriculum']->intakeLabel() }}.
                @else
                    All semesters in your programme.
                @endif
            </p>
        </div>

        <ul class="tich-semester-list tich-mt-4">
            @foreach ($otherSemesters as $semesterNumber => $units)
                @php($period = $academics['periods_by_semester']->get($semesterNumber))
                @php($status = $portalPeriodStatus($period))
                <li class="tich-semester-list__item">
                    <span class="tich-semester-list__label">Semester {{ $semesterNumber }}</span>
                    <span class="tich-semester-list__meta">
                        @if ($period?->scheduleLabel())
                            {{ $period->scheduleLabel() }}
                        @else
                            Dates not published
                        @endif
                        · {{ $units->count() }} {{ str('unit')->plural($units->count()) }}
                        @if ($status)
                            · {{ $status }}
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>

        <div class="tich-form-group tich-semester-picker tich-mt-6">
            <label for="semester-units-select" class="tich-label">View units for semester</label>
            <select id="semester-units-select" class="tich-input">
                <option value="">Choose a semester…</option>
                @foreach ($otherSemesters as $semesterNumber => $units)
                    @php($period = $academics['periods_by_semester']->get($semesterNumber))
                    <option value="semester-{{ $semesterNumber }}">
                        Semester {{ $semesterNumber }}
                        @if ($period?->scheduleLabel())
                            ({{ $period->scheduleLabel() }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        @foreach ($otherSemesters as $semesterNumber => $units)
            <div id="semester-{{ $semesterNumber }}" class="tich-semester-units-panel" hidden>
                <div class="tich-card tich-table-panel">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Contact hrs</th>
                                <th>Learning hrs</th>
                                <th>Core</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($units as $mapping)
                                <tr>
                                    <td>{{ $mapping->unit?->unit_code }} - {{ $mapping->unit?->unit_name }}</td>
                                    <td>{{ $mapping->contact_hours ?? 0 }}</td>
                                    <td>{{ $mapping->total_learning_hours ?? 0 }}</td>
                                    <td>{{ $mapping->is_compulsory ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById('semester-units-select');
        if (!select) {
            return;
        }

        var panels = document.querySelectorAll('.tich-semester-units-panel');

        select.addEventListener('change', function () {
            panels.forEach(function (panel) {
                panel.hidden = panel.id !== select.value;
            });
        });
    });
    </script>
@elseif ($academics['registered_units']->isEmpty() && ! $academics['curriculum'])
    @include('partials.states.empty', [
        'title' => 'No units yet',
        'description' => $student->program_id
            ? 'Your programme units will appear here once the curriculum is published and semester registration opens.'
            : 'Your programme has not been assigned yet. Contact the admissions office if this persists.',
        'icon' => 'inbox',
    ])
@endif
