<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employment contract signed</title>
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
                            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#6cab33;">
                                Your employment contract has been signed
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                Dear {{ $staff->fullName() }},
                            </p>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                Human Resources has recorded your employment contract as <strong>signed</strong>. Please keep this confirmation for your records.
                            </p>
                            <div style="margin:0 0 20px;padding:14px 16px;background:#f5f6f6;border-left:3px solid #1669a6;font-family:Arial,sans-serif;font-size:13px;line-height:1.7;color:#494c50;">
                                <p style="margin:0 0 6px;"><strong>Contract No.:</strong> {{ $contract->contract_number }}</p>
                                <p style="margin:0 0 6px;"><strong>Job title:</strong> {{ $contract->job_title }}</p>
                                <p style="margin:0 0 6px;"><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $contract->contract_type)) }}</p>
                                <p style="margin:0 0 6px;"><strong>Start date:</strong> {{ $contract->start_date?->format('j F Y') ?? '-' }}</p>
                                <p style="margin:0 0 6px;"><strong>End date:</strong> {{ $contract->end_date?->format('j F Y') ?? 'Ongoing' }}</p>
                                <p style="margin:0;"><strong>Signed on:</strong> {{ $contract->signed_date?->format('j F Y') ?? now()->format('j F Y') }}</p>
                            </div>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                If any detail looks incorrect, or you have not signed this contract, contact Human Resources as soon as possible.
                            </p>
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#494c50;">
                                Best regards,<br>
                                <strong>Human Resources Department</strong><br>
                                {{ $emailBrand['short_name'] ?? 'TICH in Africa' }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#1a1d21;color:#9aa3b0;font-family:Arial,sans-serif;font-size:11px;line-height:1.5;text-align:center;">
                            This is an automated message. Please do not reply to this email.
                            @include('emails.partials.legal-links')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
