<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: bold; color: #2563eb; }
        .receipt-title { font-size: 32px; font-weight: bold; color: #1f2937; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { padding: 15px; background: #f9fafb; border-radius: 8px; }
        .info-box h4 { margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .info-box p { margin: 5px 0; }
        .amount-box { text-align: center; padding: 30px; background: #eff6ff; border-radius: 8px; margin-bottom: 30px; }
        .amount-box .label { font-size: 14px; color: #6b7280; text-transform: uppercase; }
        .amount-box .amount { font-size: 36px; font-weight: bold; color: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: bold; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="logo">{{ $institution['name'] ?? 'TICH ERP' }}</div>
            <p>{{ $institution['address'] ?? '' }}</p>
            <p>{{ $institution['phone'] ?? '' }} | {{ $institution['email'] ?? '' }}</p>
        </div>
        <div style="text-align: right;">
            <div class="receipt-title">RECEIPT</div>
            <p><strong>{{ $receipt->receipt_number }}</strong></p>
            <p>Issued: {{ $receipt->issued_at?->format('d M Y H:i') }}</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4>Received From</h4>
            <p><strong>{{ $receipt->student->fullName() ?? 'N/A' }}</strong></p>
            <p>{{ $receipt->student->registration_number ?? 'N/A' }}</p>
        </div>
        <div class="info-box">
            <h4>Payment Details</h4>
            <p><strong>Method:</strong> {{ ucfirst($receipt->payment_method) }}</p>
            <p><strong>Reference:</strong> {{ $receipt->payment_reference ?? 'N/A' }}</p>
            <p><strong>Invoice:</strong> {{ $receipt->invoice->invoice_number ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="amount-box">
        <div class="label">Amount Received</div>
        <div class="amount">KES {{ number_format($receipt->amount, 2) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Payment for {{ $receipt->invoice->invoice_number ?? 'invoice' }}</td>
                <td>KES {{ number_format($receipt->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
        <p>{{ $institution['name'] ?? 'TICH ERP' }} | {{ $institution['address'] ?? '' }}</p>
    </div>
</body>
</html>
