<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application update</title>
</head>
<body style="margin:0;padding:0;background:#f5f6f6;font-family:Georgia,serif;color:#494c50;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f6f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-top:4px solid #6cab33;border-bottom:3px solid #1669a6;">
                    <tr>
                        <td style="padding:32px 28px;">
                            <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#1669a6;">
                                TICH in Africa
                            </p>
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#6cab33;">
                                Application status update
                            </h1>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#494c50;">
                                Dear {{ trim($applicant->first_name.' '.$applicant->surname) }},
                                there is an update on your application for <strong>{{ $programName }}</strong>.
                            </p>

                            <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:12px;color:#1669a6;">
                                Application number
                            </p>
                            <p style="margin:0 0 20px;font-family:Arial,sans-serif;font-size:24px;font-weight:700;letter-spacing:0.08em;color:#494c50;">
                                {{ $applicant->application_number }}
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;background:#f5f6f6;border:1px solid #e2e4e5;">
                                <tr>
                                    <td style="padding:16px 18px;font-family:Arial,sans-serif;font-size:13px;line-height:1.6;color:#494c50;">
                                        <strong>Status:</strong> {{ $statusLabel }}<br>
                                        <strong>Academic review:</strong> {{ $reviewLabel }}
                                        @if ($rejectionReason)
                                            <br><strong>Reason:</strong> {{ $rejectionReason }}
                                        @endif
                                        @if ($reviewNotes)
                                            <br><strong>Notes:</strong> {{ $reviewNotes }}
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @if ($portalActivationUrl)
                                <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 16px;">
                                    <tr>
                                        <td style="border-radius:4px;background:#6cab33;">
                                            <a href="{{ $portalActivationUrl }}" style="display:inline-block;padding:12px 20px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                                                Activate student portal
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#494c50;">
                                    You have been admitted. Use the button above to create your password and access your student portal with your registration details, documents, and enrolment information.
                                </p>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 24px;">
                                <tr>
                                    <td style="border-radius:4px;background:#1669a6;">
                                        <a href="{{ $statusUrl }}" style="display:inline-block;padding:12px 20px;font-family:Arial,sans-serif;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                                            Verify application status
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:13px;line-height:1.6;color:#494c50;">
                                Use the link above or visit the application status page with your application number and email address (<strong>{{ $applicant->email }}</strong>).
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f5f6f6;border-top:1px solid #e2e4e5;font-family:Arial,sans-serif;font-size:11px;color:#6b6e72;">
                            Tropical Institute of Community Health and Development in Africa
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
