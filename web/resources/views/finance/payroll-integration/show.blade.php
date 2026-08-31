@extends('layouts.finance')

@section('title', 'Payroll Run ' . $run->run_number)

@section('finance-content')

    <x-page-toolbar :title="'GL: ' . $run->run_number" :meta="$run->periodLabel() . ' · ' . ucfirst($run->status)">
        <x-slot:actions>
            @if ($run->canPostToGl())
                <form method="post" action="{{ route('finance.payroll-integration.post', $run) }}" style="display:inline;" onsubmit="return confirm('Post this payroll run to the general ledger?');">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Post to general ledger</button>
                </form>
            @endif
            <a href="{{ route('finance.payroll-integration.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-dept-stats tich-mt-8">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Gross salaries (expense)</p>
            <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format((float) $run->total_gross, 2) }}</p>
            <p class="tich-caption">DR {{ config('finance.accounts.salaries_expense') }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Statutory payables</p>
            <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format((float) $run->total_paye + $run->total_nssf + $run->total_sha + $run->total_ahl, 2) }}</p>
            <p class="tich-caption">CR PAYE / NSSF / SHA / AHL</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Net bank disbursement</p>
            <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format((float) $run->total_net, 2) }}</p>
            <p class="tich-caption">CR {{ config('finance.accounts.cash_bank') }}</p>
        </article>
    </div>

    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">Journal summary</h2>
        <table class="tich-admin-table tich-mt-4">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Debit (KES)</th>
                    <th>Credit (KES)</th>
                    <th>Narration</th>
                </tr>
            </thead>
            <tbody>
                @php($employerStatutory = max(0, (float) $run->total_employer_cost - (float) $run->total_gross))
                <tr>
                    <td>{{ config('finance.accounts.salaries_expense') }} Salaries expense</td>
                    <td>{{ number_format((float) $run->total_gross, 2) }}</td>
                    <td>-</td>
                    <td>Payroll gross - {{ $run->periodLabel() }}</td>
                </tr>
                @if ($employerStatutory > 0)
                    <tr>
                        <td>{{ config('finance.accounts.employer_statutory_expense') }} Employer statutory</td>
                        <td>{{ number_format($employerStatutory, 2) }}</td>
                        <td>-</td>
                        <td>Employer NSSF/AHL - {{ $run->periodLabel() }}</td>
                    </tr>
                @endif
                @if ((float) $run->total_paye > 0)
                    <tr>
                        <td>{{ config('finance.accounts.paye_payable') }} PAYE payable</td>
                        <td>-</td>
                        <td>{{ number_format((float) $run->total_paye, 2) }}</td>
                        <td>KRA remittance</td>
                    </tr>
                @endif
                @if ((float) $run->total_nssf > 0)
                    <tr>
                        <td>{{ config('finance.accounts.nssf_payable') }} NSSF payable</td>
                        <td>-</td>
                        <td>{{ number_format((float) $run->total_nssf, 2) }}</td>
                        <td>NSSF remittance</td>
                    </tr>
                @endif
                @if ((float) $run->total_sha > 0)
                    <tr>
                        <td>{{ config('finance.accounts.sha_payable') }} SHA payable</td>
                        <td>-</td>
                        <td>{{ number_format((float) $run->total_sha, 2) }}</td>
                        <td>SHA remittance</td>
                    </tr>
                @endif
                @if ((float) $run->total_ahl > 0)
                    <tr>
                        <td>{{ config('finance.accounts.ahl_payable') }} AHL payable</td>
                        <td>-</td>
                        <td>{{ number_format((float) $run->total_ahl, 2) }}</td>
                        <td>AHL remittance</td>
                    </tr>
                @endif
                <tr>
                    <td>{{ config('finance.accounts.cash_bank') }} Bank</td>
                    <td>-</td>
                    <td>{{ number_format((float) $run->total_net, 2) }}</td>
                    <td>Net salaries disbursed</td>
                </tr>
            </tbody>
        </table>
    </article>

    @if ($run->status === \App\Models\PayrollRun::STATUS_POSTED)
        <article class="tich-card tich-mt-8">
            <p class="tich-caption">Posted to GL</p>
            <p>Reference <strong>{{ $run->gl_reference }}</strong> on {{ $run->posted_at?->format('d M Y H:i') }} by {{ $run->poster?->fullName() ?? 'system' }}.</p>
        </article>
    @endif
@endsection
