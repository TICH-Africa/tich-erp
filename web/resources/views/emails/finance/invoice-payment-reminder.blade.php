<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Payment reminder</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Dear {{ $invoice->student->displayName() }},</p>
    <p>This is a friendly reminder about an outstanding invoice on your student account.</p>
    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr><td><strong>Invoice</strong></td><td>{{ $invoice->invoice_number }}</td></tr>
        <tr><td><strong>Balance due</strong></td><td>KES {{ number_format((float) $invoice->balance, 2) }}</td></tr>
        <tr><td><strong>Due date</strong></td><td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td></tr>
        <tr><td><strong>Status</strong></td><td>{{ ucfirst($invoice->status) }}</td></tr>
    </table>
    <p>Please pay promptly via M-Pesa in your <a href="{{ route('portal.dashboard', ['section' => 'finance']) }}">Student Portal</a> to avoid clearance delays.</p>
    <p style="color:#6b7280;font-size:12px;">This is an automated reminder from TICH Finance.</p>
</body>
</html>
