<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: bold; color: #2563eb; }
        .invoice-title { font-size: 32px; font-weight: bold; color: #1f2937; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { padding: 15px; background: #f9fafb; border-radius: 8px; }
        .info-box h4 { margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .info-box p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: bold; }
        .totals { display: flex; justify-content: flex-end; }
        .totals-box { width: 300px; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .totals-row.total { font-weight: bold; font-size: 18px; border-bottom: none; }
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
            <div class="invoice-title">INVOICE</div>
            <p><strong>{{ $invoice->invoice_number }}</strong></p>
            <p>Issued: {{ $invoice->issue_date?->format('d M Y') }}</p>
            <p>Due: {{ $invoice->due_date?->format('d M Y') }}</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4>Bill To</h4>
            <p><strong>{{ $invoice->student->fullName() ?? 'N/A' }}</strong></p>
            <p>{{ $invoice->student->registration_number ?? 'N/A' }}</p>
        </div>
        <div class="info-box">
            <h4>Invoice Details</h4>
            <p><strong>Type:</strong> {{ ucfirst($invoice->invoice_type) }}</p>
            <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
            <p><strong>Description:</strong> {{ $invoice->description }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Adjustments</th>
                <th>Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items ?? [] as $item)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->fee_item)) }}</td>
                    <td>{{ $item->description }}</td>
                    <td>KES {{ number_format($item->amount, 2) }}</td>
                    <td>
                        @if ($item->scholarship_adjustment > 0)
                            <p style="margin:0;font-size:12px;color:#059669;">Scholarship: -KES {{ number_format($item->scholarship_adjustment, 2) }}</p>
                        @endif
                        @if ($item->bursary_adjustment > 0)
                            <p style="margin:0;font-size:12px;color:#059669;">Bursary: -KES {{ number_format($item->bursary_adjustment, 2) }}</p>
                        @endif
                        @if ($item->waiver_adjustment > 0)
                            <p style="margin:0;font-size:12px;color:#059669;">Waiver: -KES {{ number_format($item->waiver_adjustment, 2) }}</p>
                        @endif
                    </td>
                    <td><strong>KES {{ number_format($item->net_amount, 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-box">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>KES {{ number_format($invoice->amount, 2) }}</span>
            </div>
            <div class="totals-row">
                <span>Paid</span>
                <span>KES {{ number_format($invoice->amount_paid, 2) }}</span>
            </div>
            <div class="totals-row total">
                <span>Balance</span>
                <span>KES {{ number_format($invoice->balance, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
        <p>{{ $institution['name'] ?? 'TICH ERP' }} | {{ $institution['address'] ?? '' }}</p>
    </div>
</body>
</html>
