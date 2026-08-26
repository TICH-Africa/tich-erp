<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Complete your onboarding</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    @include('emails.partials.brand-header')
    <div style="background: #1f2937; color: white; padding: 20px; text-align: center;">
        <h1>{{ config('app.name', 'TICH ERP') }}</h1>
        <p>Human Resources Department</p>
    </div>

    <div style="padding: 30px; background: #f9fafb;">
        <h2 style="color: #1f2937;">Dear {{ $staff->fullName() }},</h2>

        <p>Welcome to {{ config('app.name', 'TICH ERP') }}! We are pleased to offer you the position of <strong>{{ $staff->job_title }}</strong>.</p>

        <p>To complete your onboarding and activate your employee account, please click the button below:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/onboarding/activate/' . $staff->onboarding_token) }}" style="background: #1669a6; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">
                Complete Onboarding
            </a>
        </div>

        <p style="color: #6b7280; font-size: 14px;">This link will expire in 14 days. If you have any issues, please contact HR.</p>

        <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Your Details</h3>
            <p><strong>Employee Number:</strong> {{ $staff->employee_number }}</p>
            <p><strong>Job Title:</strong> {{ $staff->job_title }}</p>
            <p><strong>Department:</strong> {{ $staff->department->dept_name ?? '-' }}</p>
        </div>

        <p>Best regards,<br>
        <strong>Human Resources Department</strong><br>
        {{ config('app.name', 'TICH ERP') }}</p>
    </div>

    <div style="background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; font-size: 12px;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
