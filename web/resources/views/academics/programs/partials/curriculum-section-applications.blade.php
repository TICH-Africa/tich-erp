@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select an <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">intake</a> to view applications for that cohort.</p>
    </article>
@else
    @php
        $matchedApplications = $applications['matched'] ?? collect();
        $unassignedApplications = $applications['unassigned'] ?? collect();
    @endphp

    <article class="tich-card">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h3">Applications - {{ $selectedIntake->intakeLabel() }}</h2>
            <p class="tich-text">Onboarding applications for {{ $program->program_name }} targeting this intake.</p>
        </div>

        @if ($unassignedApplications->isNotEmpty())
            <div class="tich-card tich-mt-4" style="border-left: 3px solid #dc2626; padding: 1rem 1.25rem;">
                <p class="tich-text" style="margin:0;">
                    <strong>{{ $unassignedApplications->count() }}</strong>
                    {{ str('application')->plural($unassignedApplications->count()) }}
                    {{ $unassignedApplications->count() === 1 ? 'was' : 'were' }} submitted without specifying an intake.
                    They appear below until the applicant selects an intake during online application.
                </p>
            </div>
        @endif

        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Application</th>
                        <th>Applicant</th>
                        <th>Intake</th>
                        <th>Campus</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matchedApplications->concat($unassignedApplications) as $application)
                        <tr>
                            <td>{{ $application->application_number }}</td>
                            <td>
                                {{ $application->fullName() }}<br>
                                <span class="tich-caption">{{ $application->email }}</span>
                            </td>
                            <td>
                                @if ($application->intake_year && $application->intake_month)
                                    {{ \App\Models\CurriculumVersion::intakeMonths()[(int) $application->intake_month] ?? $application->intake_month }}
                                    {{ $application->intake_year }}
                                @else
                                    <span class="tich-caption" style="color:#dc2626;">Not specified</span>
                                @endif
                            </td>
                            <td>{{ $application->preferredCampus?->campus_name ?? '-' }}</td>
                            <td>{{ $application->created_at?->format('d M Y') ?? '-' }}</td>
                            <td>@include('applications.partials.status-badge', ['applicant' => $application])</td>
                            <td>
                                <a href="{{ route('departments.academics.applications.show', array_merge($hub, ['id' => $application->id])) }}" class="tich-link">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">
                                No applications for this intake yet.
                                @if ($selectedIntake->intake_year && $selectedIntake->intake_month)
                                    <span class="tich-caption">Applications must specify intake {{ $selectedIntake->intakeLabel() }} when applying online.</span>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
@endif
