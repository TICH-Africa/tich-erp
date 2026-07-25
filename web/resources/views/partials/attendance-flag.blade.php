@php
    $flag = $flag ?? 'neutral';
    $label = $label ?? \App\Services\AttendanceVerificationService::flagLabel($flag);
@endphp
<span class="tich-attendance-flag tich-attendance-flag--{{ $flag }}">{{ $label }}</span>
