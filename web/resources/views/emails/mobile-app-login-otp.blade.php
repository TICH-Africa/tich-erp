<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mobile sign-in code</title>
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
                                TICH Mobile
                            </p>
                            <h1 style="margin:0 0 16px;font-size:24px;line-height:1.3;color:#6cab33;">
                                Your sign-in code
                            </h1>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#494c50;">
                                Enter this 6-digit code in the TICH mobile app to finish signing in for the first time on this account.
                            </p>
                            <p style="margin:0 0 8px;font-family:Arial,sans-serif;font-size:12px;color:#1669a6;">
                                Sign-in code
                            </p>
                            <p style="margin:0 0 24px;font-family:Arial,sans-serif;font-size:32px;font-weight:700;letter-spacing:0.35em;color:#494c50;">
                                {{ $otp }}
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#494c50;">
                                This code expires in {{ $expiresMinutes }} minutes. If you did not try to sign in to the mobile app, you can ignore this email.
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