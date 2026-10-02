<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Work from home - {{ $wfh->request_code }}</title>
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
                                Human Resources · Work from home
                            </p>
                            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#6cab33;">
                                @if ($event === 'approved')
                                    Your work from home request was approved
                                @elseif ($event === 'rejected')
                                    Your work from home request was rejected
                                @elseif ($event === 'returned')
                                    Your work from home request needs changes
                                @else
                                    Your work from home request was submitted
                                @endif
                            </h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#494c50;">
                                Dear {{ $recipientName }},
                            </p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#494c50;">
                                @if ($event === 'approved')
                                    HR has approved your work from home request. Details are below.
                                @elseif ($event === 'rejected')
                                    HR has rejected your work from home request. Details are below.
                                @elseif ($event === 'returned')
                                    HR returned your work from home request for changes. Details are below.
                                @else
                                    Your work from home application has been received and sent to HR for review. Details are below.
                                @endif
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                                <tr>
                                    <td style="padding:16px 18px;font-family:Arial,sans-serif;font-size:13px;line-height:1.7;color:#494c50;">
                                        <p style="margin:0 0 8px;"><strong>Request code:</strong> {{ $wfh->request_code }}</p>
                                        <p style="margin:0 0 8px;"><strong>Status:</strong> {{ $wfh->statusLabel() }}</p>
                                        <p style="margin:0 0 8px;"><strong>Work date:</strong> {{ $workDate?->format('l, j F Y') ?? '-' }}</p>
                                        <p style="margin:0 0 8px;"><strong>Period:</strong>
                                            @if ($periodStart && $periodEnd && ! $periodStart->equalTo($periodEnd))
                                                {{ $periodStart->format('j M Y') }} to {{ $periodEnd->format('j M Y') }}
                                            @else
                                                {{ $workDate?->format('j M Y') ?? '-' }}
                                            @endif
                                        </p>
                                        <p style="margin:0 0 8px;"><strong>Number of days:</strong> {{ $days }} day{{ $days === 1 ? '' : 's' }}</p>
                                        @if ($wfh->start_time || $wfh->end_time)
                                            <p style="margin:0 0 8px;"><strong>Hours:</strong>
                                                {{ $wfh->start_time ? \Illuminate\Support\Str::of($wfh->start_time)->substr(0, 5) : '-' }}
                                                –
                                                {{ $wfh->end_time ? \Illuminate\Support\Str::of($wfh->end_time)->substr(0, 5) : '-' }}
                                                @if ($wfh->total_hours)
                                                    ({{ rtrim(rtrim(number_format((float) $wfh->total_hours, 2), '0'), '.') }} hrs)
                                                @endif
                                            </p>
                                        @endif
                                        @if ($wfh->department_name)
                                            <p style="margin:0 0 8px;"><strong>Department:</strong> {{ $wfh->department_name }}</p>
                                        @endif
                                        @if ($wfh->supervisor_name)
                                            <p style="margin:0;"><strong>Supervisor:</strong> {{ $wfh->supervisor_name }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @if (in_array($event, ['rejected', 'returned'], true) && $wfh->hr_notes)
                                <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#494c50;">
                                    <strong>HR notes:</strong> {{ $wfh->hr_notes }}
                                </p>
                            @endif

                            @if ($event === 'approved' && $wfh->hr_notes)
                                <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#494c50;">
                                    <strong>HR notes:</strong> {{ $wfh->hr_notes }}
                                </p>
                            @endif

                            <p style="margin:0;">
                                <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 18px;background:#1669a6;color:#ffffff;text-decoration:none;font-family:Arial,sans-serif;font-size:13px;font-weight:600;">
                                    View request in ERP
                                </a>
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
