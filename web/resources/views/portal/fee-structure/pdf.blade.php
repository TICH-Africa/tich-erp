<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fees structure - {{ $student->registration_number }}</title>
    <style>
        body {
            color: #494c50;
            font-family: dejavusans, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .brand-table {
            border-bottom: 2pt solid #6cab33;
            margin-bottom: 14pt;
        }
        .brand-table td {
            border: none;
            padding: 0 0 5pt;
            vertical-align: middle;
        }
        .brand-logo {
            height: 50pt;
            width: auto;
        }
        .institution {
            color: #6cab33;
            font-size: 15pt;
            font-weight: bold;
        }
        .institution-details {
            color: #494c50;
            font-size: 8pt;
        }
        .document-heading {
            color: #1669a6;
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 1pt;
        }
        .document-subheading {
            color: #494c50;
            font-size: 8pt;
        }
        .section-heading {
            background-color: #6cab33;
            color: #ffffff;
            font-size: 9pt;
            font-weight: bold;
            margin: 12pt 0 0;
            padding: 5pt 7pt;
        }
        .info-table td, .charges-table td, .charges-table th, .steps-table td {
            border: 0.5pt solid #dce8d0;
            padding: 5pt 7pt;
        }
        .info-table .label {
            background-color: #f3f8ec;
            color: #494c50;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            width: 22%;
        }
        .info-table .value {
            color: #1669a6;
            font-weight: bold;
        }
        .charges-table th {
            background-color: #eaf2df;
            color: #1669a6;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
        }
        .charges-table .amount, .charges-table th.amount {
            text-align: right;
            white-space: nowrap;
            width: 24%;
        }
        .charges-table tr.total td {
            background-color: #f3f8ec;
            color: #1669a6;
            font-weight: bold;
        }
        .charges-table tr.outstanding td {
            background-color: #fdf3f2;
            border-top: 1pt solid #b42318;
            color: #b42318;
            font-weight: bold;
        }
        .paybill-number {
            color: #1669a6;
            font-size: 14pt;
            font-weight: bold;
        }
        .note {
            color: #494c50;
            font-size: 8pt;
        }
        .footer {
            border-top: 0.7pt solid #dce8d0;
            color: #494c50;
            font-size: 7pt;
            margin-top: 16pt;
        }
        .footer td {
            border: none;
            padding: 6pt 0 0;
        }
        .footer-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <table class="brand-table">
        <tr>
            @if (! empty($institution['logo_src']))
                <td width="24%"><img src="{{ $institution['logo_src'] }}" class="brand-logo" alt=""></td>
            @endif
            <td>
                <div class="institution">{{ $institution['name'] ?? 'TICH ERP' }}</div>
                @if (! empty($institution['address']))
                    <div class="institution-details">{{ $institution['address'] }}</div>
                @endif
                @if (! empty($institution['website']))
                    <div class="institution-details">{{ $institution['website'] }}</div>
                @endif
            </td>
            <td class="footer-right" width="28%">
                <div class="document-heading">FEES STRUCTURE</div>
                <div class="document-subheading">Statement of charges and how to pay</div>
            </td>
        </tr>
    </table>

    <div class="section-heading">Student</div>
    <table class="info-table">
        <tr>
            <td class="label">Name</td>
            <td class="value">{{ $student->first_name }} {{ $student->surname }}</td>
            <td class="label">Admission number</td>
            <td class="value">{{ $student->registration_number }}</td>
        </tr>
        <tr>
            <td class="label">Programme</td>
            <td class="value">{{ $student->program?->program_name ?? 'Not recorded' }}</td>
            <td class="label">Year of study</td>
            <td class="value">{{ $student->current_year ? 'Year '.$student->current_year : 'Not recorded' }}</td>
        </tr>
    </table>

    <div class="section-heading">Your account</div>
    <table class="charges-table">
        <tbody>
            <tr>
                <td>Total charged to your account</td>
                <td class="amount">KES {{ number_format((float) ($account?->total_chargeable ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Total payments received</td>
                <td class="amount">KES {{ number_format((float) ($account?->total_paid ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Excess kept for the next bill</td>
                <td class="amount">KES {{ number_format((float) ($account?->credit_balance ?? 0), 2) }}</td>
            </tr>
            <tr class="outstanding">
                <td>Outstanding balance</td>
                <td class="amount">KES {{ number_format((float) ($student->overall_balance ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($openInvoices->isNotEmpty())
        <div class="section-heading">Bills open on your account</div>
        <table class="charges-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th class="amount">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($openInvoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
                        <td>KES {{ number_format((float) $invoice->amount, 2) }}</td>
                        <td>KES {{ number_format((float) $invoice->amount_paid, 2) }}</td>
                        <td class="amount">KES {{ number_format((float) $invoice->balance, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-heading">Programme charges</div>
    @if ($feeStructure)
        <table class="charges-table">
            <thead>
                <tr>
                    <th>Charge</th>
                    <th class="amount">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Application fee (paid once after application approval)</td>
                    <td class="amount">{{ number_format((float) $feeStructure->application_fee, 2) }}</td>
                </tr>
                @foreach (\App\Models\FeeStructure::SEMESTER_CHARGES as $field => $label)
                    <tr>
                        <td>{{ $label }} (each semester)</td>
                        <td class="amount">{{ number_format((float) ($feeStructure->{$field} ?? 0), 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Mandatory semester total</td>
                    <td class="amount">{{ number_format((float) $feeStructure->total_semester_fee, 2) }}</td>
                </tr>
                @foreach (\App\Models\FeeStructure::OPTIONAL_SEMESTER_CHARGES as $field => $label)
                    <tr>
                        <td>{{ $label }} (optional)</td>
                        <td class="amount">{{ number_format((float) ($feeStructure->{$field} ?? 0), 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td>Quality assurance (annual)</td>
                    <td class="amount">{{ number_format((float) $feeStructure->qa_annual_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>Indexing NCK (once per programme)</td>
                    <td class="amount">{{ $feeStructure->requires_indexing_nck ? number_format((float) $feeStructure->indexing_nck_fee, 2) : 'Not applicable' }}</td>
                </tr>
                <tr>
                    <td>Graduation fee (after completion)</td>
                    <td class="amount">{{ number_format((float) $feeStructure->graduation_fee, 2) }}</td>
                </tr>
            </tbody>
        </table>
        <p class="note">Charges shown are the approved schedule for {{ $feeStructure->program?->program_name }} in {{ $feeStructure->academicYear?->year_label ?? 'the current academic year' }}. Your bills may include or exclude items according to what the school has approved for you.</p>
    @else
        <p class="note">The approved fee schedule for your programme has not been published yet. The amounts billed to you are shown above.</p>
    @endif

    <div class="section-heading">How to pay by M-Pesa</div>
    <table class="info-table">
        <tr>
            <td class="label">School Paybill Number</td>
            <td class="value paybill-number">{{ $paybill !== '' ? $paybill : 'Issued by the finance office' }}</td>
        </tr>
        <tr>
            <td class="label">Business / Account Name</td>
            <td class="value">{{ $institution['name'] ?? 'TICH ERP' }}</td>
        </tr>
        <tr>
            <td class="label">Account Number</td>
            <td class="value paybill-number">{{ $student->registration_number }}</td>
        </tr>
        <tr>
            <td class="label">Payment Method</td>
            <td class="value">Safaricom M-Pesa Paybill</td>
        </tr>
    </table>
    <table class="steps-table">
        <tr>
            <td width="6%"><strong>1</strong></td>
            <td>Open M-Pesa on your phone.</td>
        </tr>
        <tr>
            <td><strong>2</strong></td>
            <td>Select Lipa na M-Pesa.</td>
        </tr>
        <tr>
            <td><strong>3</strong></td>
            <td>Select Pay Bill.</td>
        </tr>
        <tr>
            <td><strong>4</strong></td>
            <td>Enter the school's Paybill number.</td>
        </tr>
        <tr>
            <td><strong>5</strong></td>
            <td>Enter your student admission number as the account number.</td>
        </tr>
        <tr>
            <td><strong>6</strong></td>
            <td>Enter the amount you wish to pay. You may pay the whole outstanding balance or part of it.</td>
        </tr>
        <tr>
            <td><strong>7</strong></td>
            <td>Enter your M-Pesa PIN to authorize the payment.</td>
        </tr>
        <tr>
            <td><strong>8</strong></td>
            <td>Wait for the M-Pesa confirmation message. The payment shows on your student portal account, and money sent above what has been billed stays on your account and comes off the next bill.</td>
        </tr>
    </table>
    <p class="note"><strong>Important:</strong> The account number must always be your actual admission number, exactly as printed above. A payment with any other account number cannot be matched to you and Safaricom returns it.</p>

    <table class="footer">
        <tr>
            <td>Generated {{ $generatedAt->format('d M Y, H:i') }}</td>
            <td class="footer-right">{{ $institution['name'] ?? 'TICH ERP' }} · All amounts in Kenyan Shillings (KES)</td>
        </tr>
    </table>
</body>
</html>
