<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Class attendance</h1>
    <p class="tich-text">Sessions are generated from your timetable and semester schedule. Each session includes an auto-built student roster from course enrolment. Print the sign-in sheet, collect signatures, mark attendance online, then upload photos.</p>
</header>

@if (session('status'))
    <p class="tich-text tich-mt-4" style="color:var(--tich-success, #15803d);">{{ session('status') }}</p>
@endif

@if (! empty($attendanceSync) && $attendanceSync['created'] > 0)
    <article class="tich-card tich-mt-4">
        <p class="tich-text">{{ $attendanceSync['created'] }} new session(s) were generated from your timetable.</p>
    </article>
@endif

<article class="tich-card tich-mt-6">
    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:center;">
        <div>
            <h2 class="tich-h3" style="margin:0;">Scheduled sessions</h2>
            <p class="tich-caption">Auto-created from your lesson timetable. Select a session to take attendance.</p>
        </div>
        <form method="POST" action="{{ route('staff.attendance.sync-timetable') }}">
            @csrf
            <button type="submit" class="tich-btn tich-btn-secondary">Refresh from timetable</button>
        </form>
    </div>

    @if ($upcomingAttendanceSessions->isNotEmpty())
        <div class="tich-table-wrap tich-mt-4">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Unit</th>
                    <th>Intake</th>
                    <th>Time</th>
                    <th>Students</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($upcomingAttendanceSessions as $session)
                    <tr @if ($attendanceSession && $attendanceSession->id === $session->id) style="background:var(--tich-blue-light, #eef6fc);" @endif>
                        <td>{{ $session->session_date?->format('d M Y') }}</td>
                        <td>{{ $session->allocation?->unit?->unit_code }}</td>
                        <td>{{ $session->allocation?->intake_label ?? $session->allocation?->semester?->semester_label ?? '-' }}</td>
                        <td>{{ substr((string) $session->start_time, 0, 5) }} - {{ substr((string) $session->end_time, 0, 5) }}</td>
                        <td>{{ $session->total_expected_attendees ?? $session->records_count ?? 0 }}</td>
                        <td>
                            @if ($session->is_locked)
                                <span class="tich-caption">{{ str_replace('_', ' ', $session->verification_status ?? 'submitted') }}</span>
                            @elseif ($session->signed_sheet_image_path)
                                <span class="tich-caption">Ready to submit</span>
                            @elseif ($session->records->where('is_present', true)->isNotEmpty())
                                <span class="tich-caption">Marked - upload photos</span>
                            @else
                                <span class="tich-caption">Pending</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('staff.dashboard', ['section' => 'attendance', 'attendance_session' => $session->id]) }}" class="tich-link">Open</a>
                            ·
                            <a href="{{ route('staff.attendance.sheet', $session) }}" target="_blank" class="tich-link">Print</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @else
        <p class="tich-text tich-mt-4">No sessions yet. Ensure you have timetable slots assigned, then click refresh above.</p>
    @endif
</article>

@if ($attendanceSession)
    <article class="tich-card tich-mt-6">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:start;">
            <div>
                <p class="tich-caption">Tracking ID</p>
                <p class="tich-h3" style="margin:0; color:var(--tich-blue);">{{ $attendanceSession->session_number }}</p>
                <p class="tich-text tich-mt-2">
                    {{ $attendanceSession->allocation?->unit?->unit_code }} · {{ $attendanceSession->session_date?->format('d M Y') }}
                    · {{ substr((string) $attendanceSession->start_time, 0, 5) }} - {{ substr((string) $attendanceSession->end_time, 0, 5) }}
                    @if ($attendanceSession->venue)
                        · {{ $attendanceSession->venue }}
                    @endif
                </p>
                @if (! empty($attendanceSessionIntake))
                    <p class="tich-caption" style="color:var(--tich-blue, #1669a6);">Intake: {{ $attendanceSessionIntake }} · {{ $attendanceSession->allocation?->semester?->semester_label }}</p>
                @endif
                <p class="tich-caption">{{ $attendanceSession->records->count() }} students on roster (auto-generated from enrolment)</p>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
                <a href="{{ route('staff.attendance.sheet', $attendanceSession) }}" target="_blank" class="tich-btn tich-btn-secondary">Print sign-in sheet</a>
                <span class="tich-attendance-flag tich-attendance-flag--{{ $attendanceSession->verification_status === 'registrar_verified' ? 'green' : ($attendanceSession->verification_status === 'hod_verified' ? 'amber' : 'neutral') }}">
                    {{ str_replace('_', ' ', ucfirst($attendanceSession->verification_status ?? 'draft')) }}
                </span>
            </div>
        </div>

        <ol class="tich-attendance-steps tich-mt-6">
            <li @class(['is-done' => $attendanceStep >= 1, 'is-current' => $attendanceStep === 1])>
                <strong>Print sign-in sheet</strong> - Hand to students to sign in class.
            </li>
            <li @class(['is-done' => $attendanceStep >= 2, 'is-current' => $attendanceStep === 2])>
                <strong>Collect signatures</strong> - Physical sign-in during the lesson.
            </li>
            <li @class(['is-done' => $attendanceStep >= 3, 'is-current' => $attendanceStep === 3])>
                <strong>Mark attendance online</strong> - Tick present students below.
            </li>
            <li @class(['is-done' => $attendanceStep >= 4, 'is-current' => $attendanceStep === 4])>
                <strong>Upload signed sheet photo</strong> - Camera capture of the signed paper.
            </li>
            <li @class(['is-done' => $attendanceStep >= 5, 'is-current' => $attendanceStep === 5])>
                <strong>Upload class photo</strong> - Optional photo of students present.
            </li>
            <li @class(['is-done' => $attendanceStep >= 6, 'is-current' => $attendanceStep === 6])>
                <strong>Submit &amp; lock</strong> - Sends record for HOD/Registrar verification.
            </li>
        </ol>

        @if ($attendanceSession->is_locked)
            <p class="tich-caption tich-mt-4">Submitted {{ $attendanceSession->submitted_at?->format('d M Y H:i') ?? '-' }}. This session is locked.</p>
        @endif

        @if ($errors->any())
            <p class="tich-text tich-mt-4" style="color:var(--tich-danger, #b91c1c);">{{ $errors->first() }}</p>
        @endif

        @if ($attendanceSession->records->isEmpty())
            <p class="tich-text tich-mt-4" style="color:var(--tich-danger, #b91c1c);">No students on the roster. Enrol students in this unit for the current intake/semester, then refresh sessions.</p>
        @endif

        <form method="POST" action="{{ route('staff.attendance.save', $attendanceSession) }}" class="tich-mt-6">
            @csrf
            <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead><tr><th>Present</th><th>Reg. no.</th><th>Student</th><th>Signature line (print)</th></tr></thead>
                <tbody>
                    @foreach ($attendanceSession->records as $record)
                        <tr>
                            <td>
                                <input type="checkbox" name="present[]" value="{{ $record->student_id }}" @checked($record->is_present) @disabled($attendanceSession->is_locked)>
                            </td>
                            <td>{{ $record->student?->registration_number }}</td>
                            <td>{{ $record->student?->applicant?->fullName() ?? '-' }}</td>
                            <td class="tich-caption">________________</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @unless ($attendanceSession->is_locked)
                <div class="tich-mt-4" style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <button type="submit" class="tich-btn tich-btn-secondary">Save marks</button>
                    <button type="submit" name="lock" value="1" class="tich-btn tich-btn-primary" @disabled(! $attendanceSession->signed_sheet_image_path)>Submit &amp; lock</button>
                </div>
                @unless ($attendanceSession->signed_sheet_image_path)
                    <p class="tich-caption tich-mt-2">Upload the signed sheet photo before submitting.</p>
                @endunless
            @endunless
        </form>

        @unless ($attendanceSession->is_locked)
            <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem; align-items:start;">
                <form method="POST" action="{{ route('staff.attendance.sheet.upload', $attendanceSession) }}" enctype="multipart/form-data" style="border-top:1px solid var(--tich-neutral-border); padding-top:1.25rem;">
                    @csrf
                    <h3 class="tich-h3">Signed sheet photo</h3>
                    <p class="tich-caption">Take a photo of the signed attendance sheet.</p>
                    <div class="tich-form-group">
                        <input type="file" name="signed_sheet" class="tich-input" accept="image/*" capture="environment" @if (! $attendanceSession->signed_sheet_image_path) required @endif>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-primary">{{ $attendanceSession->signed_sheet_image_path ? 'Replace' : 'Upload' }}</button>
                    @if ($attendanceSession->signed_sheet_image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($attendanceSession->signed_sheet_image_path) }}" alt="Signed sheet" class="tich-mt-4" style="max-width:100%; border:1px solid var(--tich-neutral-border); border-radius:0.5rem;">
                    @endif
                </form>

                <form method="POST" action="{{ route('staff.attendance.class-photo.upload', $attendanceSession) }}" enctype="multipart/form-data" style="border-top:1px solid var(--tich-neutral-border); padding-top:1.25rem;">
                    @csrf
                    <h3 class="tich-h3">Class photo</h3>
                    <p class="tich-caption">Optional photo of students who were present.</p>
                    <div class="tich-form-group">
                        <input type="file" name="class_photo" class="tich-input" accept="image/*" capture="environment">
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">{{ $attendanceSession->class_photo_image_path ? 'Replace' : 'Upload' }}</button>
                    @if ($attendanceSession->class_photo_image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($attendanceSession->class_photo_image_path) }}" alt="Class photo" class="tich-mt-4" style="max-width:100%; border:1px solid var(--tich-neutral-border); border-radius:0.5rem;">
                    @endif
                </form>
            </div>
        @else
            @if ($attendanceSession->signed_sheet_image_path)
                <div class="tich-mt-6">
                    <h3 class="tich-h3">Signed sheet</h3>
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($attendanceSession->signed_sheet_image_path) }}" alt="Signed sheet" style="max-width:100%; border:1px solid var(--tich-neutral-border); border-radius:0.5rem;">
                </div>
            @endif
            @if ($attendanceSession->class_photo_image_path)
                <div class="tich-mt-6">
                    <h3 class="tich-h3">Class photo</h3>
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($attendanceSession->class_photo_image_path) }}" alt="Class photo" style="max-width:100%; border:1px solid var(--tich-neutral-border); border-radius:0.5rem;">
                </div>
            @endif
        @endunless
    </article>
@endif

<details class="tich-card tich-mt-8">
    <summary class="tich-h3" style="cursor:pointer;">Manual session (ad-hoc)</summary>
    <p class="tich-caption tich-mt-2">For classes outside the timetable. Requires an HOD-approved lesson plan.</p>
    @if ($errors->has('lesson_plan'))
        <p class="tich-text" style="color:var(--tich-danger, #b91c1c); margin-top:0.75rem;">{{ $errors->first('lesson_plan') }}</p>
    @endif
    @if ($portalData['allocations']->isEmpty())
        <p class="tich-text tich-mt-4">You need a unit allocation first.</p>
    @else
        <form method="POST" action="{{ route('staff.attendance.store') }}" class="tich-mt-4">
            @csrf
            <div class="tich-form-group">
                <label class="tich-label">Unit</label>
                <select name="allocation_id" class="tich-input" required>
                    @foreach ($portalData['allocations'] as $allocation)
                        <option value="{{ $allocation->id }}">{{ $allocation->unit?->unit_code }} · {{ $allocation->intake_label ?? $allocation->semester?->semester_label }}</option>
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
            <button type="submit" class="tich-btn tich-btn-secondary">Create manual session</button>
        </form>
    @endif
</details>

@if ($portalData['attendance_alerts']->isNotEmpty())
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Unit attendance flags</h2>
        <div class="tich-table-wrap tich-mt-4">
        <table class="tich-admin-table">
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
        </div>
    </article>
@endif
