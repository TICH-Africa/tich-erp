<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Update your employee profile</title>
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
                                My Employee Portal
                            </p>
                            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#6cab33;">
                                Please update your profile details
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                Dear {{ $staff->fullName() }}, {{ $departmentLabel }} has asked you to review and update the following items in your employee profile:
                            </p>
                            <ul style="margin:0 0 20px;padding-left:20px;font-family:Arial,sans-serif;font-size:14px;line-height:1.7;color:#494c50;">
                                @foreach ($fieldLabels as $label)
                                    <li>{{ $label }}</li>
                                @endforeach
                            </ul>
                            @if ($prompt->notes)
                                <p style="margin:0 0 20px;padding:12px 14px;background:#f5f6f6;border-left:3px solid #1669a6;font-size:13px;line-height:1.6;color:#494c50;">
                                    <strong>Note from {{ $departmentLabel }}:</strong><br>
                                    {{ $prompt->notes }}
                                </p>
                            @endif
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#494c50;">
                                Open My Employee Portal using the button below. The requested items will be highlighted for you to complete.
                            </p>
                            <p style="margin:0;">
                                <a href="{{ $portalUrl }}" style="display:inline-block;padding:12px 18px;background:#1669a6;color:#ffffff;text-decoration:none;font-family:Arial,sans-serif;font-size:13px;font-weight:600;">
                                    Open My Employee Portal
                                </a>
                            </p>
                            @if ($prompt->expires_at)
                                <p style="margin:20px 0 0;font-size:12px;line-height:1.5;color:#6b6e72;">
                                    This request is active until {{ $prompt->expires_at->format('j F Y') }}.
                                </p>
                            @endif
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
