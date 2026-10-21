<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate your TICH student portal</title>
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
                                Student portal
                            </p>
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#6cab33;">
                                Activate your student portal
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                Dear {{ $student->displayName() }},
                            </p>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#494c50;">
                                Your registration number is <strong>{{ $student->registration_number }}</strong>.
                                @if ($programmeLabel !== '')
                                    You are enrolled in {{ $programmeLabel }}@if ($campusLabel !== '') at {{ $campusLabel }}@endif.
                                @endif
                                Click the button below to set your password and sign in to the TICH student portal.
                            </p>
                            <p style="margin:0 0 28px;text-align:center;">
                                <a href="{{ $activationUrl }}" style="display:inline-block;background:#1669a6;color:#ffffff;padding:14px 28px;text-decoration:none;border-radius:6px;font-family:Arial,sans-serif;font-weight:700;font-size:14px;">
                                    Activate student portal
                                </a>
                            </p>
                            <p style="margin:0 0 12px;font-size:12px;line-height:1.6;color:#6b6e72;word-break:break-all;">
                                Or open this link:<br>{{ $activationUrl }}
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#494c50;">
                                This link expires on {{ $student->portal_invite_expires_at?->format('j F Y, g:i A') }}. If you did not expect this email, contact Admissions or ICT.
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
