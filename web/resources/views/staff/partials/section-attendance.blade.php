<header class="tich-dept-header">
    <h1 class="tich-h1 tich-dept-header__title">Attendance register</h1>
    <p class="tich-text">Log class attendance. Students below 90% are flagged for exam eligibility review.</p>
</header>

@if ($attendanceSession)
    <article class="tich-card tich-mt-6">
        <h2 class="tich-h3">{{ $attendanceSession->allocation?->unit?->unit_code }} · {{ $attendanceSession->session_date?->format('d M Y') }}</h2>
        @if ($attendanceSession->is_locked)
            <p class="tich-caption">This session is locked.</p>
        @endif
        <form method="POST" action="{{ route('staff.attendance.save', $attendanceSession) }}" class="tich-mt-4">
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
                <div class="tich-mt-4" style="display:flex; gap:1rem;">
                    <button type="submit" class="tich-btn tich-btn-secondary">Save</button>
                    <button type="submit" name="lock" value="1" class="tich-btn tich-btn-primary">Save &amp; lock</button>
                </div>
            @endunless
        </form>
    </article>
@endif

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items:start; gap:1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">New session</h2>
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
                <button type="submit" class="tich-btn tich-btn-primary">Create session</button>
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
                @if ($session->is_locked) <span class="tich-caption">· Locked</span> @endif
            </p>
        @empty
            <p class="tich-text tich-mt-4">No sessions yet.</p>
        @endforelse
    </article>
</div>
