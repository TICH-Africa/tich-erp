<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Invoice issued</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Dear {{ $invoice->student->displayName() }},</p>
    <p>A new invoice has been issued to your student account.</p>
    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr><td><strong>Invoice</strong></td><td>{{ $invoice->invoice_number }}</td></tr>
        <tr><td><strong>Registration</strong></td><td>{{ $invoice->student->registration_number }}</td></tr>
        <tr><td><strong>Programme</strong></td><td>{{ $invoice->student->program?->program_name ?? '-' }}</td></tr>
        <tr><td><strong>Amount</strong></td><td>KES {{ number_format((float) $invoice->amount, 2) }}</td></tr>
        <tr><td><strong>Due date</strong></td><td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td></tr>
    </table>
    <p>{{ $invoice->description }}</p>
    <p>View and pay this invoice in your <a href="{{ route('portal.dashboard', ['section' => 'finance']) }}">Student Portal</a>.</p>
    <p style="color:#6b7280;font-size:12px;">This is an automated message from TICH Finance. Please do not reply to this email.</p>
</body>
</html>
