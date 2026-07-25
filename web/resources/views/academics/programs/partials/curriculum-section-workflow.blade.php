<article class="tich-card">
    <div class="tich-dept-panel__head">        <h2 class="tich-h3">Intake workflow</h2>
        <p class="tich-text">
            All intakes for {{ $program->program_name }}, including drafts and intakes awaiting approval.
            Use the working intake selector above when editing a specific cohort.
        </p>
    </div>

    @if ($intakes->isEmpty())
        <p class="tich-text tich-mt-4">
            No intakes yet.
            <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">Create an intake</a>
            to start the approval workflow.
        </p>
    @else
        <div class="tich-card tich-mt-4" style="overflow-x:auto; padding:0;">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Intake</th>
                        <th>Format</th>
                        <th>Units</th>
                        <th>Status</th>
                        <th>Workflow</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($intakes as $intake)
                        @php
                            $intakeMappings = $intake->relationLoaded('items') ? $intake->items : collect();
                            $hasInactiveUnits = $intakeMappings->contains(fn ($map) => ($map->unit?->status ?? '') !== 'active');
                            $isSelected = $selectedIntake?->id === $intake->id;
                        @endphp
                        <tr @if ($isSelected) style="background:var(--tich-blue-light,#d6e8f5);" @endif>
                            <td>
                                <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'intake' => $intake->id, 'section' => 'semesters'])) }}" class="tich-link">
                                    {{ $intake->intakeLabel() }}
                                </a>
                                @if ($isSelected)
                                    <br><span class="tich-caption">Working intake</span>
                                @endif
                            </td>
                            <td>{{ $formats[$intake->curriculum_format] ?? $intake->curriculum_format }}</td>
                            <td>{{ $intakeMappings->count() }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $intake->status)) }}</td>
                            <td class="tich-caption">
                                @if ($intake->submitted_at)
                                    Submitted {{ $intake->submitted_at->format('d M Y') }}
                                @elseif ($intake->registrar_approved_at)
                                    Registrar approved {{ $intake->registrar_approved_at->format('d M Y') }}
                                @elseif ($intake->published_at)
                                    Published {{ $intake->published_at->format('d M Y') }}
                                @else
                                    Not submitted
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                @if ($intake->status === 'draft')
                                    @can('academics.write')
                                        @if ($intakeMappings->isEmpty())
                                            <span class="tich-caption">Add units first</span>
                                        @elseif ($hasInactiveUnits)
                                            <span class="tich-caption">Approve mapped units</span>
                                        @else
                                            <form method="POST" action="{{ route('departments.academics.versions.submit', array_merge($hub, ['version' => $intake->id])) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tich-link">Submit for approval</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="tich-caption">Draft</span>
                                    @endcan
                                @elseif ($intake->status === 'pending_registry')
                                    @if ($canApproveRegistry)
                                        <form method="POST" action="{{ route('departments.academics.versions.approve-registry', array_merge($hub, ['version' => $intake->id])) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tich-link">Registrar approve</button>
                                        </form>
                                    @else
                                        <span class="tich-caption">Awaiting registrar</span>
                                    @endif
                                @elseif ($intake->status === 'pending_ceo')
                                    @if ($canApproveCeo)
                                        <form method="POST" action="{{ route('departments.academics.versions.approve-ceo', array_merge($hub, ['version' => $intake->id])) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="tich-link">CEO publish</button>
                                        </form>
                                    @else
                                        <span class="tich-caption">Awaiting CEO</span>
                                    @endif
                                @elseif ($intake->status === 'published')
                                    <span class="tich-caption">Live</span>
                                @elseif ($intake->status === 'superseded')
                                    <span class="tich-caption">Superseded</span>
                                @else
                                    <span class="tich-caption">{{ ucwords(str_replace('_', ' ', $intake->status)) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</article>
