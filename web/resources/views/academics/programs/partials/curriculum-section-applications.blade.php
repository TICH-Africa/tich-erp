@if (! $selectedIntake)
    <article class="tich-card">
        <p class="tich-text">Select an <a href="{{ route('departments.academics.programs.curriculum', array_merge($hub, ['program' => $program->id, 'section' => 'intakes'])) }}" class="tich-link">intake</a> to view applications for that cohort.</p>
    </article>
@else
    <article class="tich-card">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h3">Applications — {{ $selectedIntake->intakeLabel() }}</h2>
            <p class="tich-text">Onboarding applications for {{ $program->program_name }} targeting this intake.</p>
        </div>

        <div class="tich-card tich-mt-4" style="overflow-x:auto; padding:0;">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Application</th>
                        <th>Applicant</th>
                        <th>Campus</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($applications as $application)
                        <tr>
                            <td>{{ $application->application_number }}</td>
                            <td>
                                {{ $application->fullName() }}<br>
                                <span class="tich-caption">{{ $application->email }}</span>
                            </td>
                            <td>{{ $application->preferredCampus?->campus_name ?? '—' }}</td>
                            <td>{{ $application->created_at?->format('d M Y') ?? '—' }}</td>
                            <td>@include('admissions.partials.status-badge', ['applicant' => $application])</td>
                            <td>
                                <a href="{{ route('admissions.applications.show', $application->id) }}" class="tich-link">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:2rem;text-align:center;" class="tich-text">
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
