@extends('layouts.hr')

@section('title', 'Payroll Run ' . $run->run_number)

@section('hr-content')
    <x-page-toolbar :title="$run->run_number" :meta="$run->periodLabel() . ' · ' . $run->staff_count . ' staff · ' . ucfirst($run->status)">
        <x-slot:actions>
            @if ($run->isEditable())
                <form method="post" action="{{ route('hr.payroll.runs.recalculate', $run) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-secondary">Recalculate</button>
                </form>
            @endif
            @if ($run->canApprove())
                @can('hr.manage_contracts')
                    <form method="post" action="{{ route('hr.payroll.runs.approve', $run) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-primary">Approve run</button>
                    </form>
                @endcan
            @endif
            @if ($run->status === \App\Models\PayrollRun::STATUS_APPROVED)
                <span class="tich-badge">Ready for GL posting</span>
            @endif
            @if ($run->isEditable())
                <form method="post" action="{{ route('hr.payroll.runs.cancel', $run) }}" style="display:inline;" onsubmit="return confirm('Cancel this draft payroll run?');">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-ghost">Cancel</button>
                </form>
            @endif
            <a href="{{ route('hr.payroll.runs.index') }}" class="tich-btn tich-btn-ghost">All runs</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-8">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Gross pay</p>
            <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format((float) $run->total_gross, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Net pay</p>
            <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format((float) $run->total_net, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">PAYE / WHT</p>
            <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format((float) $run->total_paye, 2) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Employer cost</p>
            <p class="tich-stat__value" style="font-size:1rem;">KES {{ number_format((float) $run->total_employer_cost, 2) }}</p>
        </article>
    </div>

    @if ($run->status !== \App\Models\PayrollRun::STATUS_DRAFT)
        <section class="tich-dept-panel tich-mt-8">
            <div class="tich-dept-panel__head">
                <h2 class="tich-h2 tich-dept-panel__title">Statutory filing exports</h2>
            </div>
            <div class="tich-flex" style="gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ route('hr.payroll.runs.statutory.export', [$run, 'kra']) }}" class="tich-btn tich-btn-secondary">KRA (PAYE/WHT) CSV</a>
                <a href="{{ route('hr.payroll.runs.statutory.export', [$run, 'nssf']) }}" class="tich-btn tich-btn-secondary">NSSF CSV</a>
                <a href="{{ route('hr.payroll.runs.statutory.export', [$run, 'sha']) }}" class="tich-btn tich-btn-secondary">SHA/SHIF CSV</a>
            </div>
        </section>
    @endif

    <section class="tich-dept-panel tich-mt-8">
        <div class="tich-dept-panel__head">
            <h2 class="tich-h2 tich-dept-panel__title">Payslips</h2>
        </div>
        <div class="tich-card tich-table-panel">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net</th>
                        <th>Payslip</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($run->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->staff?->fullName() }}</strong>
                                <p class="tich-caption">{{ $item->staff?->employee_number }}</p>
                            </td>
                            <td>{{ $item->staff?->department?->dept_name ?? '—' }}</td>
                            <td>KES {{ number_format((float) $item->gross_salary, 2) }}</td>
                            <td>KES {{ number_format((float) $item->total_deductions, 2) }}</td>
                            <td><strong>KES {{ number_format((float) $item->net_salary, 2) }}</strong></td>
                            <td class="tich-caption">{{ $item->payslip_number }}</td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('hr.payroll.runs.item.payslip', $item) }}" class="tich-btn tich-btn-ghost" target="_blank" rel="noopener">View</a>
                                <a href="{{ route('hr.payroll.runs.item.payslip.pdf', $item) }}" class="tich-btn tich-btn-ghost">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-table-empty">No staff with pay in this run.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($run->notes)
        <article class="tich-card tich-mt-8">
            <p class="tich-caption">Notes</p>
            <p>{{ $run->notes }}</p>
        </article>
    @endif
@endsection
