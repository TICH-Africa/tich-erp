<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Attendance verification</h1>
    <p class="tich-text">Dual-layer verification: print a signed sheet, match the digital roster, upload the signed sheet photo, then submit for HOD and Registrar review.</p>
</header>

<article class="tich-card tich-mt-6">
    <h2 class="tich-h3">Attendance risk mitigation matrix</h2>
    <p class="tich-caption">Per-unit participation rates drive exam eligibility flags automatically.</p>
    @include('staff.partials.attendance-risk-matrix')
</article>

@if ($attendanceSession)
    @php
        $step = 1;
        if ($attendanceSession->records->isNotEmpty()) {
            $step = 2;
        }
        if ($attendanceSession->signed_sheet_image_path) {
            $step = 4;
        }
        if ($attendanceSession->is_locked) {
            $step = 5;
        }
    @endphp

    <article class="tich-card tich-mt-6">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
            <div>
                <p class="tich-caption">Tracking ID</p>
                <p class="tich-h3" style="margin:0; color:var(--tich-blue);">{{ $attendanceSession->session_number }}</p>
                <p class="tich-text tich-mt-2">{{ $attendanceSession->allocation?->unit?->unit_code }} · {{ $attendanceSession->session_date?->format('d M Y') }}</p>
            </div>
            <div>
                <span class="tich-attendance-flag tich-attendance-flag--{{ $attendanceSession->verification_status === 'registrar_verified' ? 'green' : ($attendanceSession->verification_status === 'hod_verified' ? 'amber' : 'neutral') }}">
                    {{ str_replace('_', ' ', ucfirst($attendanceSession->verification_status ?? 'draft')) }}
                </span>
            </div>
        </div>

        <ol class="tich-attendance-steps tich-mt-6">
            <li @class(['is-done' => $step >= 1, 'is-current' => $step === 1])>
                <strong>Generate sheet</strong> - Print the session sheet with the tracking ID for physical signatures.
                <div class="tich-mt-2">
                    <a href="{{ route('staff.attendance.sheet', $attendanceSession) }}" target="_blank" class="tich-btn tich-btn-secondary">Print attendance sheet</a>
                </div>
            </li>
            <li @class(['is-done' => $step >= 2, 'is-current' => $step === 2])>
                <strong>Physical collection</strong> - Students sign the printed sheet during class.
            </li>
            <li @class(['is-done' => $step >= 3, 'is-current' => $step === 3])>
                <strong>Roster verification</strong> - Submit the generated roster for HOD/Registrar verification before marking attendance.
                @if ($attendanceSession->records->isNotEmpty() && ! $attendanceSession->roster_verified_at)
                    <div class="tich-mt-2">
                        <form method="POST" action="{{ route('staff.attendance.submit-roster', $attendanceSession) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="tich-btn tich-btn-primary">Submit roster for verification</button>
                        </form>
                    </div>
                @endif
                @if ($attendanceSession->roster_verified_at)
                    <p class="tich-caption tich-mt-2">Roster verified {{ $attendanceSession->roster_verified_at->format('d M Y H:i') }}</p>
                @endif
            </li>
            <li @class(['is-done' => $step >= 4, 'is-current' => $step === 4])>
                <strong>Digital roster matching</strong> - Tick present students to match the physical signatures.
            </li>
            <li @class(['is-done' => $step >= 5, 'is-current' => $step === 5])>
                <strong>Upload signed sheet</strong> - Capture or upload a photo of the signed paper sheet.
            </li>
            <li @class(['is-done' => $step >= 6, 'is-current' => $step === 6])>
                <strong>HOD &amp; Registrar verification</strong> - Submitted records enter the secure attendance ledger.
            </li>
        </ol>

        @if ($attendanceSession->is_locked)
            <p class="tich-caption tich-mt-4">Submitted {{ $attendanceSession->submitted_at?->format('d M Y H:i') ?? '-' }}. This session is locked.</p>
        @endif

        <form method="POST" action="{{ route('staff.attendance.save', $attendanceSession) }}" class="tich-mt-6">
            @csrf
            <table class="tich-admin-table">
                <thead><tr><th>Present</th><th>Reg. no.</th><th>Student</th></tr></thead>
                <tbody>
                    @foreach ($attendanceSession->records as $record)
                        <tr>
                            <td>
                                <input type="checkbox" name="present[]" value="{{ $record->student_id }}" @checked($record->is_present) @disabled($attendanceSession->is_locked)>
                            </td>
                            <td>{{ $record->student?->registration_number }}</td>
                            <td>{{ $record->student?->applicant?->fullName() ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @unless ($attendanceSession->is_locked)
                <div class="tich-mt-4" style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <button type="submit" class="tich-btn tich-btn-secondary">Save roster draft</button>
                    <button type="submit" name="lock" value="1" class="tich-btn tich-btn-primary" @disabled(! $attendanceSession->signed_sheet_image_path)>Submit &amp; lock</button>
                </div>
                @unless ($attendanceSession->signed_sheet_image_path)
                    <p class="tich-caption tich-mt-2">Upload the signed sheet photo before submitting.</p>
                @endunless
            @endunless
        </form>

        @unless ($attendanceSession->is_locked)
            <form method="POST" action="{{ route('staff.attendance.sheet.upload', $attendanceSession) }}" enctype="multipart/form-data" class="tich-mt-6" style="border-top:1px solid var(--tich-neutral-border); padding-top:1.25rem;">
                @csrf
                <h3 class="tich-h3">Signed sheet photo</h3>
                <p class="tich-caption">Use your device camera or upload an image. This becomes the unalterable supporting document.</p>
                <div class="tich-form-group">
                    <input type="file" name="signed_sheet" class="tich-input" accept="image/*" capture="environment" required>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Upload signed sheet</button>
            </form>
        @endunless

        @if ($attendanceSession->signed_sheet_image_path)
            <div class="tich-mt-6">
                <h3 class="tich-h3">Supporting document</h3>
                <img src="{{ asset('storage/'.$attendanceSession->signed_sheet_image_path) }}" alt="Signed attendance sheet" style="max-width:100%; border:1px solid var(--tich-neutral-border); border-radius:0.5rem;">
                @if ($attendanceSession->sheet_image_hash)
                    <p class="tich-caption tich-mt-2">Integrity hash: {{ $attendanceSession->sheet_image_hash }}</p>
                @endif
            </div>
        @endif
    </article>
@endif

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items:start; gap:1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">New session</h2>
        <p class="tich-caption">Requires an HOD-approved lesson plan for the same unit and date.</p>
        @if ($errors->has('lesson_plan'))
            <p class="tich-text" style="color:var(--tich-danger, #b91c1c); margin-top:0.75rem;">{{ $errors->first('lesson_plan') }}</p>
        @endif
        @if ($portalData['allocations']->isEmpty())
            <p class="tich-text tich-mt-4">You need a unit allocation before taking attendance.</p>
        @else
            <form method="POST" action="{{ route('staff.attendance.store') }}" class="tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label">Unit</label>
                    <select name="allocation_id" class="tich-input" required>
                        @foreach ($portalData['allocations'] as $allocation)
                            <option value="{{ $allocation->id }}">{{ $allocation->unit?->unit_code }} · {{ $allocation->semester?->semester_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Date</label>
                    <input type="date" name="session_date" class="tich-input" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="tich-form-group">
                    <label class="tich-label">Venue</label>
                    <input type="text" name="venue" class="tich-input">
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Create session &amp; generate sheet</button>
            </form>
        @endif
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Recent sessions</h2>
        @forelse ($portalData['attendance_sessions']->take(15) as $session)
            <p class="tich-text tich-mt-2">
                <a href="{{ route('staff.dashboard', ['section' => 'attendance', 'attendance_session' => $session->id]) }}" class="tich-link">
                    {{ $session->unit_code }} · {{ \Illuminate\Support\Carbon::parse($session->session_date)->format('d M Y') }}
                </a>
                @if ($session->is_locked)
                    <span class="tich-caption">· {{ str_replace('_', ' ', $session->verification_status ?? 'submitted') }}</span>
                @endif
            </p>
        @empty
            <p class="tich-text tich-mt-4">No sessions yet.</p>
        @endforelse
    </article>
</div>

@if ($portalData['attendance_alerts']->isNotEmpty())
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Unit attendance flags</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead><tr><th>Student</th><th>Unit</th><th>Percentage</th><th>Status</th></tr></thead>
            <tbody>
                @foreach ($portalData['attendance_alerts'] as $alert)
                    <tr>
                        <td>{{ trim($alert->student_name) ?: $alert->registration_number }}</td>
                        <td>{{ $alert->unit_code }}</td>
                        <td>{{ number_format((float) $alert->attendance_percentage, 1) }}%</td>
                        <td><span class="tich-attendance-flag tich-attendance-flag--{{ $alert->status_flag }}">{{ \App\Services\AttendanceVerificationService::flagLabel($alert->status_flag) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </article>
@endif
