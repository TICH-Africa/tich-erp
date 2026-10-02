<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $audience === 'inviter' ? 'Invitee registered' : 'Welcome to TICH ERP' }}</title>
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
                                ERP registration
                            </p>

                            @if ($audience === 'inviter')
                                <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#6cab33;">
                                    Your invitee has signed up
                                </h1>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                    <strong>{{ $registrantName }}</strong> ({{ $registrant->email }}) completed registration using the invitation you sent.
                                </p>
                                <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#494c50;">
                                    This confirmation was sent only to you as the inviter. Sign in to the ERP if you need to review their staff profile or access.
                                </p>
                            @else
                                <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#6cab33;">
                                    Welcome to TICH ERP
                                </h1>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                    Dear {{ $registrantName }},
                                </p>
                                <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                    Your ERP account has been created successfully for <strong>{{ $registrant->email }}</strong>.
                                    You can sign in with this email and the password you set during registration.
                                </p>
                                <p style="margin:0 0 24px;text-align:center;">
                                    <a href="{{ $loginUrl }}" style="display:inline-block;background:#1669a6;color:#ffffff;padding:14px 28px;text-decoration:none;border-radius:6px;font-family:Arial,sans-serif;font-weight:700;font-size:14px;">
                                        Sign in to TICH ERP
                                    </a>
                                </p>
                            @endif
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
