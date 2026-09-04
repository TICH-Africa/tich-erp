@php
    $attendance = $academics['attendance'] ?? collect();
    $examPortal = $academics['exam_portal'] ?? [];
    $upcomingExams = $examPortal['upcoming_exams'] ?? collect();
    $feeCleared = strtolower((string) ($student->fee_clearance_status ?? '')) === 'cleared';
@endphp

<section class="tich-portal-panel tich-mt-6">
    <div class="tich-portal-panel__head">
        <div>
            <h2 class="tich-h3">Exam eligibility checker</h2>
            <p class="tich-caption tich-mt-1">
                Institutional rules require fee clearance and typically ≥ 90% class attendance before an exam card is issued.
            </p>
        </div>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-4">
        <article class="tich-card">
            <h3 class="tich-h4">Fee clearance</h3>
            <p class="tich-mt-2">
                @if ($feeCleared)
                    <span class="tich-badge tich-badge--success">Cleared</span>
                @else
                    <span class="tich-badge tich-badge--warning">{{ ucfirst($student->fee_clearance_status ?? 'Pending') }}</span>
                @endif
            </p>
            <p class="tich-caption tich-mt-2">Outstanding balance: {{ number_format((float) ($portalData['finance']['summary']['outstanding_balance'] ?? $student->overall_balance ?? 0), 2) }}</p>
        </article>
        <article class="tich-card">
            <h3 class="tich-h4">Attendance threshold</h3>
            <p class="tich-caption tich-mt-2">Per-unit attendance from classroom sessions. Target: 90%.</p>
        </article>
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-panel__head">
            <h3 class="tich-table-panel__title">Unit attendance &amp; eligibility</h3>
        </div>
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Attendance</th>
                        <th>Sessions</th>
                        <th>Meets 90%</th>
                        <th>Exam status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendance as $row)
                        @php
                            $pct = (float) ($row->attendance_percentage ?? 0);
                            $meets = $pct >= 90;
                            $examMatch = $upcomingExams->firstWhere('unit_id', $row->unit_id)
                                ?? $upcomingExams->firstWhere('unit_code', $row->unit_code ?? null);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $row->unit_code ?? 'Unit' }}</strong>
                                <span class="tich-caption" style="display:block;">{{ $row->unit_name ?? '' }}</span>
                            </td>
                            <td>{{ number_format($pct, 1) }}%</td>
                            <td>{{ (int) ($row->sessions_present ?? 0) }} / {{ (int) ($row->sessions_total ?? 0) }}</td>
                            <td>
                                @if ($meets)
                                    <span class="tich-badge tich-badge--success">Yes</span>
                                @else
                                    <span class="tich-badge tich-badge--danger">No</span>
                                @endif
                            </td>
                            <td>
                                @if ($examMatch)
                                    @if ($examMatch->eligible_for_exams)
                                        <span class="tich-badge tich-badge--success">Eligible</span>
                                    @else
                                        <span class="tich-badge tich-badge--warning">Not cleared</span>
                                    @endif
                                @else
                                    <span class="tich-caption">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="tich-table-empty">No attendance summaries yet for this semester.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
