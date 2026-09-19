<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password reset code</title>
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
                                TICH in Africa
                            </p>
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#6cab33;">
                                Password reset code
                            </h1>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#494c50;">
                                We received a request to reset your TICH ERP password. Use this one-time code to set a new password.
                            </p>
                            <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:12px;color:#1669a6;">
                                Reset code
                            </p>
                            <p style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:32px;font-weight:700;letter-spacing:0.35em;color:#494c50;">
                                {{ $otp }}
                            </p>
                            <p style="margin:0 0 16px;font-size:13px;line-height:1.6;color:#494c50;">
                                This code expires in {{ $expiresMinutes }} minutes. If you did not request a reset, you can ignore this email.
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#6b6e72;">
                                After 3 reset attempts in 7 days, further requests are escalated to the ICT / Technical team.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f5f6f6;border-top:1px solid #e2e4e5;font-family:Arial,sans-serif;font-size:11px;color:#6b6e72;">
                            Tropical Institute of Community Health and Development in Africa · ICT
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
