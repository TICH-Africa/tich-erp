<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fee structure - {{ $feeStructure->program?->program_name }}</title>
    <style>
        body {
            color: #494c50;
            font-family: dejavusans, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2pt solid #6cab33;
            margin-bottom: 18pt;
            padding-bottom: 12pt;
        }
        .brand-table, .summary-table, .charges-table {
            border-collapse: collapse;
            width: 100%;
        }
        .brand-logo {
            height: 52pt;
            width: auto;
        }
        .brand-logo-cell {
            padding-bottom: 5pt;
            text-align: center;
        }
        .institution {
            color: #6cab33;
            font-size: 15pt;
            font-weight: bold;
            text-align: center;
        }
        .institution-details {
            color: #494c50;
            font-size: 8pt;
            text-align: center;
        }
        .document-heading {
            color: #1669a6;
            font-size: 19pt;
            font-weight: bold;
            letter-spacing: 1pt;
            text-align: center;
        }
        .document-subheading {
            color: #494c50;
            font-size: 8pt;
            text-align: center;
        }
        .summary-table {
            margin-bottom: 16pt;
        }
        .summary-table td {
            background-color: #f3f8ec;
            border: 0.5pt solid #dce8d0;
            padding: 8pt;
            width: 50%;
        }
        .summary-label {
            color: #494c50;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .summary-value {
            color: #1669a6;
            font-size: 10pt;
            font-weight: bold;
        }
        .section-heading {
            background-color: #6cab33;
            color: #ffffff;
            font-size: 9pt;
            font-weight: bold;
            margin: 13pt 0 0;
            padding: 6pt 8pt;
        }
        .section-note {
            color: #494c50;
            font-size: 8pt;
            margin: 4pt 0 6pt;
        }
        .charges-table {
            margin-bottom: 8pt;
        }
        .charges-table th {
            background-color: #eaf2df;
            color: #1669a6;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
        }
        .charges-table th, .charges-table td {
            border-bottom: 0.5pt solid #dce8d0;
            padding: 6pt 8pt;
        }
        .charges-table td.amount, .charges-table th.amount {
            text-align: right;
            white-space: nowrap;
            width: 29%;
        }
        .charges-table tr.total td {
            background-color: #f3f8ec;
            border-top: 1pt solid #6cab33;
            color: #1669a6;
            font-weight: bold;
        }
        .muted {
            color: #494c50;
            font-size: 8pt;
        }
        .footer {
            border-top: 0.7pt solid #dce8d0;
            color: #494c50;
            font-size: 7pt;
            margin-top: 18pt;
            padding-top: 7pt;
        }
        .footer-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="brand-table">
            <tr>
                <td colspan="2" class="brand-logo-cell">
                    @if (! empty($institution['logo_src']))
                        <img src="{{ $institution['logo_src'] }}" class="brand-logo" alt="">
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="institution">{{ $institution['name'] ?? 'TICH ERP' }}</div>
                    @if (! empty($institution['address']))
                        <div class="institution-details">{{ $institution['address'] }}</div>
                    @endif
                    @if (! empty($institution['website']))
                        <div class="institution-details">{{ $institution['website'] }}</div>
                    @endif
                    <div class="document-heading">FEES STRUCTURE</div>
                    <div class="document-subheading">Official programme fee schedule</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Programme</div>
                <div class="summary-value">{{ $feeStructure->program?->program_name ?? 'Not specified' }}</div>
            </td>
            <td>
                <div class="summary-label">Academic year</div>
                <div class="summary-value">{{ $feeStructure->academicYear?->year_label ?? 'Not specified' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="summary-label">Effective from</div>
                <div class="summary-value">{{ $feeStructure->effective_from?->format('d M Y') ?? 'Not specified' }}</div>
            </td>
            <td>
                <div class="summary-label">Approval status</div>
                <div class="summary-value">{{ $feeStructure->is_approved ? 'Approved' : 'Pending approval' }}</div>
                @if ($feeStructure->is_approved && $feeStructure->approver)
                    <div class="muted">Approved by {{ $feeStructure->approver->fullName() }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="section-heading">Application fee · paid once after application approval</div>
    <table class="charges-table">
        <thead>
            <tr><th>Charge</th><th class="amount">Amount (KES)</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Application fee</td>
                <td class="amount">{{ number_format((float) $feeStructure->application_fee, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-heading">Semester charges · billed each semester</div>
    <table class="charges-table">
        <thead>
            <tr><th>Charge</th><th class="amount">Amount (KES)</th></tr>
        </thead>
        <tbody>
            @foreach (\App\Models\FeeStructure::SEMESTER_CHARGES as $field => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="amount">{{ number_format((float) ($feeStructure->{$field} ?? 0), 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Mandatory semester total</td>
                <td class="amount">{{ number_format((float) $feeStructure->total_semester_fee, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-heading">Optional semester charges</div>
    <p class="section-note">Optional charges are excluded from the mandatory semester total.</p>
    <table class="charges-table">
        <thead>
            <tr><th>Charge</th><th class="amount">Amount (KES)</th></tr>
        </thead>
        <tbody>
            @foreach (\App\Models\FeeStructure::OPTIONAL_SEMESTER_CHARGES as $field => $label)
                <tr>
                    <td>{{ $label }}@if ($field === 'transport_fee') <span class="muted">(per booklet)</span> @endif</td>
                    <td class="amount">{{ number_format((float) ($feeStructure->{$field} ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-heading">Annual and programme charges</div>
    <table class="charges-table">
        <thead>
            <tr><th>Charge</th><th class="amount">Frequency</th><th class="amount">Amount (KES)</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Quality assurance</td>
                <td class="amount">Annual</td>
                <td class="amount">{{ number_format((float) $feeStructure->qa_annual_fee, 2) }}</td>
            </tr>
            <tr>
                <td>Indexing (NCK)</td>
                <td class="amount">Once per programme</td>
                <td class="amount">
                    {{ $feeStructure->requires_indexing_nck ? number_format((float) $feeStructure->indexing_nck_fee, 2) : 'Not applicable' }}
                </td>
            </tr>
            <tr>
                <td>Graduation fee</td>
                <td class="amount">Once, after programme completion</td>
                <td class="amount">{{ number_format((float) $feeStructure->graduation_fee, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td>Generated {{ $generatedAt->format('d M Y, H:i') }}</td>
            <td class="footer-right">{{ $institution['name'] ?? 'TICH ERP' }} · Fees are shown in Kenyan Shillings (KES)</td>
        </tr>
    </table>
</body>
</html>
