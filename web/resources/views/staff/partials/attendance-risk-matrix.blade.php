<table class="tich-admin-table tich-attendance-matrix tich-mt-4">
    <thead>
        <tr>
            <th>Attendance bound</th>
            <th>Status flag</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($attendanceRiskMatrix ?? \App\Services\AttendanceVerificationService::riskMatrix() as $row)
            <tr>
                <td>{{ $row['bound'] }}</td>
                <td><span class="tich-attendance-flag tich-attendance-flag--{{ $row['flag'] }}">{{ $row['label'] }}</span></td>
                <td class="tich-text">{{ $row['action'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
