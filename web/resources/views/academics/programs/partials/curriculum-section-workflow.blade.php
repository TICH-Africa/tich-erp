@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select an <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">intake</a> to manage its approval workflow.</p>
    </article>
@else
    <article class="tich-card">
        <h2 class="tich-h3">Intake workflow</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Intake</th>
                    <th>Format</th>
                    <th>Units</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $selectedIntake->intakeLabel() }}</td>
                    <td>{{ $formats[$selectedIntake->curriculum_format] ?? $selectedIntake->curriculum_format }}</td>
                    <td>{{ $mappings->count() }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $selectedIntake->status)) }}</td>
                    <td style="white-space:nowrap;">
                        @if ($selectedIntake->status === 'draft')
                            @can('academics.write')
                                @if ($mappings->isEmpty())
                                    <span class="tich-caption">Add units in Semester units first</span>
                                @elseif ($mappings->contains(fn ($map) => ($map->unit?->status ?? '') !== 'active'))
                                    <span class="tich-caption">Approve all mapped units first</span>
                                @else
                                    <form method="POST" action="{{ route('departments.academics.versions.submit', array_merge($hub, ['version' => $selectedIntake->id])) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-link">Submit for approval</button>
                                    </form>
                                @endif
                            @endcan
                        @endif
                        @if ($selectedIntake->status === 'pending_registry' && $canApproveRegistry)
                            <form method="POST" action="{{ route('departments.academics.versions.approve-registry', array_merge($hub, ['version' => $selectedIntake->id])) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="tich-link">Registrar approve</button>
                            </form>
                        @endif
                        @if ($selectedIntake->status === 'pending_ceo' && $canApproveCeo)
                            <form method="POST" action="{{ route('departments.academics.versions.approve-ceo', array_merge($hub, ['version' => $selectedIntake->id])) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="tich-link">CEO publish</button>
                            </form>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </article>
@endif
