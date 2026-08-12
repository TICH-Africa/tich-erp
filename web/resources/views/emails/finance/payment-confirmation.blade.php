<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Payment confirmation</title></head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Dear {{ $payment->student->displayName() }},</p>
    <p>We have received your payment. Thank you.</p>
    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr><td><strong>Payment ref</strong></td><td>{{ $payment->payment_number }}</td></tr>
        <tr><td><strong>Invoice</strong></td><td>{{ $payment->invoice?->invoice_number ?? '-' }}</td></tr>
        <tr><td><strong>Amount</strong></td><td>KES {{ number_format((float) $payment->amount, 2) }}</td></tr>
        <tr><td><strong>Method</strong></td><td>{{ config('finance.payment_methods.'.$payment->payment_method, ucfirst($payment->payment_method)) }}</td></tr>
        <tr><td><strong>Date</strong></td><td>{{ $payment->payment_date?->format('d M Y') ?? '-' }}</td></tr>
    </table>
    <p>Your updated balance is available in the <a href="{{ route('portal.dashboard', ['section' => 'finance']) }}">Student Portal</a>.</p>
    <p style="color:#6b7280;font-size:12px;">This is an automated message from TICH Finance. Please do not reply to this email.</p>
</body>
</html>
