<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Application Received</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    @include('emails.partials.brand-header')
    <div style="background: #1f2937; color: white; padding: 20px; text-align: center;">
        <h1>{{ $emailBrand['short_name'] ?? 'TICH in Africa' }}</h1>
        <p>Human Resources Department</p>
    </div>

    <div style="padding: 30px; background: #f9fafb;">
        <h2 style="color: #1f2937;">Dear {{ $application->full_name }},</h2>

        <p>Thank you for applying for the position of <strong>{{ $vacancy->job_title }}</strong> at {{ $emailBrand['short_name'] ?? 'TICH in Africa' }}.</p>

        <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Application Details</h3>
            <p><strong>Application Number:</strong> {{ $application->application_number }}</p>
            <p><strong>Position:</strong> {{ $vacancy->job_title }}</p>
            <p><strong>Department:</strong> {{ $vacancy->department->dept_name ?? 'General' }}</p>
            <p><strong>Application Date:</strong> {{ $application->created_at->format('F j, Y') }}</p>
            <p><strong>Status:</strong> Submitted for Review</p>
        </div>

        <h3>What happens next?</h3>
        <ol>
            <li>Our HR team will review your application</li>
            <li>If shortlisted, you will be contacted for an interview</li>
            <li>You can track your application status using your application number</li>
        </ol>

        <div style="background: #eff6ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Track your application:</strong></p>
            <p style="margin: 5px 0 0;">Visit: <a href="{{ url('/vacancies/track') }}">{{ url('/vacancies/track') }}</a></p>
            <p style="margin: 5px 0 0;">Application Number: <strong>{{ $application->application_number }}</strong></p>
        </div>

        <p>If you have any questions, please contact the Human Resources department.</p>

        <p>Best regards,<br>
        <strong>Human Resources Department</strong><br>
        {{ $emailBrand['short_name'] ?? 'TICH in Africa' }}</p>
    </div>

    <div style="background: #1f2937; color: #9ca3af; padding: 20px; text-align: center; font-size: 12px;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>
