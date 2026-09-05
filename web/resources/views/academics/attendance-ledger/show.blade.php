@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => ($learningDepartment ?? null)?->id ?? request()->integer('learning_department') ?: null,
        ]);
        $statusLabel = str_replace('_', ' ', ucfirst((string) $session->verification_status));
    @endphp

    <div class="tich-mb-6">
        <a href="{{ route('departments.academics.attendance-ledger.index', $hub) }}" class="tich-btn tich-btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to ledger</a>
    </div>

    <section class="tich-leave-hero tich-mb-8">
        <div>
            <p class="tich-caption">Attendance submission</p>
            <h1 class="tich-leave-hero__title">{{ $unit?->unit_code }} - {{ $unit?->unit_name }}</h1>
            <div class="tich-leave-hero__meta">
                <span class="tich-badge">{{ $statusLabel }}</span>
                <span class="tich-caption">Tracking ID {{ $trackingId }}</span>
                <span class="tich-caption">{{ $presentCount }}/{{ $totalCount }} present</span>
            </div>
        </div>
    </section>

    <div class="tich-grid tich-grid--2" style="align-items:start; gap:1.5rem;">
        <article class="tich-card">
            <h2 class="tich-h3">Session details</h2>
            <dl class="tich-mt-4" style="display:grid; grid-template-columns:9rem 1fr; gap:0.5rem 1rem;">
                <dt class="tich-caption">Date</dt>
                <dd>{{ $session->session_date?->format('d M Y') ?? '-' }}</dd>
                <dt class="tich-caption">Time</dt>
                <dd>
                    @if ($session->start_time || $session->end_time)
                        {{ substr((string) $session->start_time, 0, 5) }} – {{ substr((string) $session->end_time, 0, 5) }}
                    @else
                        -
                    @endif
                </dd>
                <dt class="tich-caption">Venue</dt>
                <dd>{{ $session->venue ?: '-' }}</dd>
                <dt class="tich-caption">Tutor</dt>
                <dd>{{ $tutor?->fullName() ?? '-' }}</dd>
                <dt class="tich-caption">Semester</dt>
                <dd>{{ $allocation?->semester?->semester_label ?? '-' }}</dd>
                <dt class="tich-caption">Intake</dt>
                <dd>{{ $intakeLabel ?: '-' }}</dd>
                <dt class="tich-caption">Submitted</dt>
                <dd>{{ $session->submitted_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt class="tich-caption">HOD verified</dt>
                <dd>
                    @if ($session->hod_verified_at)
                        {{ $session->hodVerifier?->fullName() ?? 'HOD' }}
                        · {{ $session->hod_verified_at->format('d M Y H:i') }}
                    @else
                        Pending
                    @endif
                </dd>
                <dt class="tich-caption">Registrar verified</dt>
                <dd>
                    @if ($session->registrar_verified_at)
                        {{ $session->registrarVerifier?->fullName() ?? 'Registrar' }}
                        · {{ $session->registrar_verified_at->format('d M Y H:i') }}
                    @else
                        Pending
                    @endif
                </dd>
            </dl>
        </article>

        <article class="tich-card">
            <h2 class="tich-h3">Attachments</h2>
            <div class="tich-mt-4" style="display:grid; gap:1rem;">
                <div>
                    <p class="tich-caption" style="margin:0 0 0.35rem;">Signed attendance sheet</p>
                    @if ($session->signed_sheet_image_path)
                        <a href="{{ asset('storage/'.$session->signed_sheet_image_path) }}" target="_blank" rel="noopener" class="tich-link">
                            Open signed sheet photo
                        </a>
                        <div class="tich-mt-3">
                            <img
                                src="{{ asset('storage/'.$session->signed_sheet_image_path) }}"
                                alt="Signed attendance sheet"
                                style="max-width:100%; border:1px solid var(--tich-neutral-border); border-radius:var(--radius-sm);"
                            >
                        </div>
                    @else
                        <p class="tich-text" style="margin:0;">No signed sheet uploaded.</p>
                    @endif
                </div>
                <div>
                    <p class="tich-caption" style="margin:0 0 0.35rem;">Class photo</p>
                    @if ($session->class_photo_image_path)
                        <a href="{{ asset('storage/'.$session->class_photo_image_path) }}" target="_blank" rel="noopener" class="tich-link">
                            Open class photo
                        </a>
                    @else
                        <p class="tich-text" style="margin:0;">No class photo uploaded.</p>
                    @endif
                </div>
            </div>

            @if ($canVerifyHod && $session->verification_status === 'submitted')
                <form method="POST" action="{{ route('departments.academics.attendance-ledger.verify-hod', array_merge($hub, ['session' => $session->id])) }}" class="tich-mt-6">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Verify as HOD</button>
                </form>
            @endif

            @if ($canVerifyRegistrar && in_array($session->verification_status, ['submitted', 'hod_verified'], true))
                <form method="POST" action="{{ route('departments.academics.attendance-ledger.verify-registrar', array_merge($hub, ['session' => $session->id])) }}" class="tich-mt-4">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">
                        {{ $session->verification_status === 'hod_verified' ? 'Verify as Registrar' : 'Verify as Acdemic Registrar' }}
                    </button>
                </form>
                @if ($session->verification_status === 'submitted')
                    <p class="tich-caption tich-mt-2">Review the roster and signed sheet above before verifying.</p>
                @endif
            @endif

            @if ($session->verification_status === 'registrar_verified')
                <p class="tich-caption tich-mt-6">This submission is fully verified.</p>
            @endif
        </article>
    </div>

    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <div>
                <h2 class="tich-h3">Submitted roster</h2>
                <p class="tich-caption tich-mt-1">{{ $presentCount }} present · {{ max($totalCount - $presentCount, 0) }} absent · {{ $totalCount }} total</p>
            </div>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Registration no.</th>
                            <th>Student name</th>
                            <th>Status</th>
                            <th>Sign-in time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $index => $record)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $record->student?->registration_number ?? '-' }}</td>
                                <td>{{ $record->student?->applicant?->fullName() ?? $record->student?->fullName() ?? '-' }}</td>
                                <td>
                                    @if ($record->is_present)
                                        <span class="tich-badge">Present</span>
                                    @else
                                        <span class="tich-caption">Absent</span>
                                    @endif
                                </td>
                                <td class="tich-caption">
                                    {{ $record->sign_in_time ? \Illuminate\Support\Carbon::parse($record->sign_in_time)->format('H:i') : '-' }}
                                </td>
                            </tr>
                        @empty
                            @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No roster records for this session', 'icon' => 'inbox'])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
