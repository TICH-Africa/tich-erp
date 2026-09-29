<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RFQ Invitation</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f8f9fa; border-radius: 8px; padding: 30px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #1d4ed8; margin: 0;">TICH ERP</h1>
            <p style="color: #64748b; margin: 5px 0 0;">Procurement Module</p>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;">

        <h2 style="color: #1e293b; margin-top: 0;">Invitation to Quote</h2>

        <p>Dear <strong>{{ $supplier->contact_person ?? $supplier->supplier_name }}</strong>,</p>

        <p>You have been invited to submit a quotation for the following Request for Quotation:</p>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569; width: 140px;">RFQ Number:</td>
                    <td style="padding: 8px 0; color: #1e293b;">{{ $rfq->rfq_number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">Item/Service:</td>
                    <td style="padding: 8px 0; color: #1e293b;">{{ $rfq->item_description }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">Quantity:</td>
                    <td style="padding: 8px 0; color: #1e293b;">{{ number_format((float) $rfq->quantity, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">Category:</td>
                    <td style="padding: 8px 0; color: #1e293b;">{{ ucfirst(str_replace('_', ' ', $rfq->minimum_categories ? implode(', ', $rfq->minimum_categories) : '-')) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">Submission Deadline:</td>
                    <td style="padding: 8px 0; color: #1e293b;">{{ $rfq->submission_deadline?->format('d M Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; font-weight: 600; color: #475569;">Delivery Location:</td>
                    <td style="padding: 8px 0; color: #1e293b;">{{ $rfq->delivery_location ?? 'As specified in RFQ' }}</td>
                </tr>
            </table>
        </div>

        <p style="margin-top: 30px;">
            <a href="{{ url('/procurement/rfqs/' . $rfq->id) }}" style="display: inline-block; background: #1d4ed8; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                View RFQ Details
            </a>
        </p>

        <p style="font-size: 14px; color: #64748b; margin-top: 30px;">
            Please submit your quotation before the deadline. Late submissions may not be considered.
        </p>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">

        <p style="font-size: 12px; color: #94a3b8; text-align: center; margin: 0;">
            This invitation was sent from TICH ERP Procurement System.<br>
            Reference: {{ $invitation->rfq_id }}-{{ $invitation->supplier_id }}
        </p>
    </div>
</body>
</html>