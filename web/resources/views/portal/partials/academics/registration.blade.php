@php
    $registered = $academics['registered_units'] ?? collect();
    $curriculumUnits = $academics['curriculum_units'] ?? collect();
@endphp

<section class="tich-portal-panel tich-mt-6">
    <div class="tich-portal-panel__head">
        <div>
            <h2 class="tich-h3">Unit registration</h2>
            <p class="tich-caption tich-mt-1">
                Units registered for the active semester. Self-service registration opens during the institutional window;
                HOD and Academic Registrar can also register students when building semester schedules.
            </p>
        </div>
    </div>

    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-panel__head">
            <h3 class="tich-table-panel__title">Registered units</h3>
            <p class="tich-table-panel__meta">{{ $registered->count() }} unit(s)</p>
        </div>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Unit</th>
                        <th>Credits</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registered as $unit)
                        <tr>
                            <td><strong>{{ $unit->unit_code ?? $unit->code ?? '—' }}</strong></td>
                            <td>{{ $unit->unit_name ?? $unit->name ?? '—' }}</td>
                            <td>{{ $unit->credit_hours ?? $unit->credits ?? '—' }}</td>
                            <td><span class="tich-badge tich-badge--success">Registered</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="tich-table-empty">
                                No units registered yet for this semester. Contact your HOD or Academic Registrar if the registration window is open.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($curriculumUnits->isNotEmpty())
        <p class="tich-caption tich-mt-4">
            Programme plan lists {{ $curriculumUnits->count() }} curriculum unit(s). See <a class="tich-link" href="{{ route('portal.dashboard', ['section' => 'academics', 'tab' => 'units']) }}">My Units</a> for the full plan.
        </p>
    @endif
</section>
