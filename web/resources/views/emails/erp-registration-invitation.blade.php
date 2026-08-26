<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Register on TICH ERP</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    @include('emails.partials.brand-header')
    <div style="background: #1f2937; color: white; padding: 20px; text-align: center;">
        <h1>{{ config('app.name', 'TICH ERP') }}</h1>
        <p>{{ $departmentLabel }}</p>
    </div>

    <div style="padding: 30px; background: #f9fafb;">
        <h2 style="color: #1f2937;">Dear {{ $staff?->fullName() ?? 'Colleague' }},</h2>

        <p>You have been invited to create your account on the TICH ERP portal. Use the button below to complete registration with your personal email address (<strong>{{ $invitation->email }}</strong>).</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $registerUrl }}" style="background: #1669a6; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">
                Register on TICH ERP
            </a>
        </div>

        <p style="color: #6b7280; font-size: 14px;">This invitation link expires on {{ $invitation->expires_at->format('j F Y, g:i A') }}. If you did not expect this email, please contact ICT or HR.</p>

        @if ($staff)
        <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Your details</h3>
            <p><strong>Employee number:</strong> {{ $staff->employee_number }}</p>
            <p><strong>Job title:</strong> {{ $staff->job_title }}</p>
            <p><strong>Department:</strong> {{ $staff->department->dept_name ?? '-' }}</p>
        </div>
        @endif

        <p>Best regards,<br>
        <strong>{{ $departmentLabel }}</strong><br>
        {{ config('app.name', 'TICH ERP') }}</p>
    </div>

    <div style="background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; font-size: 12px;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
