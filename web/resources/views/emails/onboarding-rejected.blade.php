<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Onboarding Update Required</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    @include('emails.partials.brand-header')
    <div style="background: #1f2937; color: white; padding: 20px; text-align: center;">
        <h1>{{ config('app.name', 'TICH ERP') }}</h1>
        <p>Human Resources Department</p>
    </div>

    <div style="padding: 30px; background: #f9fafb;">
        <h2 style="color: #1f2937;">Dear {{ $staff->fullName() }},</h2>

        <p>Thank you for completing your onboarding information. Our HR team has reviewed your submission and requires some updates.</p>

        <div style="background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;">
            <h3 style="margin-top: 0; color: #92400e;">Reason for Return</h3>
            <p style="margin: 0;">{{ $rejectionReason }}</p>
        </div>

        <p>Please click the link below to update your information and resubmit:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/onboarding/activate/' . $staff->onboarding_token) }}" style="background: #1669a6; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;">
                Update Onboarding Information
            </a>
        </div>

        <p style="color: #6b7280; font-size: 14px;">This link will expire in 14 days. If you have any questions, please contact HR.</p>

        <p>Best regards,<br>
        <strong>Human Resources Department</strong><br>
        {{ config('app.name', 'TICH ERP') }}</p>
    </div>

    <div style="background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; font-size: 12px;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
