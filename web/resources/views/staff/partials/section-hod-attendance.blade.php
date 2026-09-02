<x-page-toolbar title="HOD Attendance review" :meta="$staff->department?->dept_name . ' · Management'" />

<div class="tich-mt-6">
    <p class="tich-text tich-mt-2">Lecturer-submitted attendance sheets pending or completed verification.</p>
    @if ($hodManagement['attendance']->isEmpty())
        <p class="tich-text tich-mt-4">No submitted attendance sessions.</p>
    @else
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead><tr><th>Tutor</th><th>Unit</th><th>Date</th><th>Status</th><th>Signed sheet</th><th>HOD</th><th>Registrar</th></tr></thead>
                <tbody>
                    @foreach ($hodManagement['attendance'] as $session)
                        <tr>
                            <td>{{ $session->tutor_name }}</td>
                            <td>{{ $session->unit_code }} - {{ $session->unit_name }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($session->session_date)->format('d M Y') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $session->verification_status)) }}</td>
                            <td>
                                @if ($session->signed_sheet_image_path)
                                    <a href="{{ asset('storage/'.$session->signed_sheet_image_path) }}" target="_blank" class="tich-link">View photo</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $session->hod_verified_at ? \Illuminate\Support\Carbon::parse($session->hod_verified_at)->format('d M Y') : '-' }}</td>
                            <td>{{ $session->registrar_verified_at ? \Illuminate\Support\Carbon::parse($session->registrar_verified_at)->format('d M Y') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.attendance-ledger.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Attendance ledger</a> in Academics to verify.</p>
    @endif
</div>
