<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject }}</title>
</head>
<body style="margin:0;padding:0;background:#f5f6f6;font-family:Georgia,serif;color:#494c50;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f6f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-top:4px solid #6cab33;border-bottom:3px solid #1669a6;">
                    <tr>
                        <td style="padding:32px 28px;">
                            @include('emails.partials.brand-header')
                            <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#1669a6;">
                                Human Resources
                            </p>
                            <h1 style="margin:0 0 16px;font-size:20px;line-height:1.3;color:#6cab33;">
                                {{ $mailSubject }}
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                Dear {{ $staff->fullName() }},
                            </p>
                            <div style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#494c50;font-family:Arial,sans-serif;white-space:pre-wrap;">{{ $bodyText }}</div>
                            <p style="margin:0 0 8px;font-size:12px;line-height:1.5;color:#6b6e72;">
                                Reference: disciplinary case {{ $case->case_number }} (incident date {{ $case->incident_date->format('j F Y') }}).
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#6b6e72;">
                                This message was sent from Human Resources. Please reply to this email if you have questions.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f5f6f6;border-top:1px solid #e2e4e5;font-family:Arial,sans-serif;font-size:11px;color:#6b6e72;">
                            {{ $emailBrand['institution_name'] ?? 'Tropical Institute of Community Health and Development in Africa' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
