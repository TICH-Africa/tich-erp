@extends('layouts.finance')

@section('title', 'Payroll → GL Integration')

@section('finance-content')
    @php($dept = $departmentParams ?? ['department' => $department->id])

    <x-page-toolbar title="Payroll → GL integration" meta="Post approved HR payroll runs to the general ledger">
        <x-slot:actions>
            <a href="{{ route('finance.employee.payroll.runs.index') }}" class="tich-btn tich-btn-secondary">Payroll runs</a>
            <a href="{{ route('finance.employee.index') }}" class="tich-btn tich-btn-ghost">Employee finance</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Run</th>
                    <th>Period</th>
                    <th>Staff</th>
                    <th>Gross</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th>GL reference</th>
                    <th>Posted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr>
                        <td>{{ $run->run_number }}</td>
                        <td>{{ $run->periodLabel() }}</td>
                        <td>{{ $run->staff_count }}</td>
                        <td>KES {{ number_format((float) $run->total_gross, 2) }}</td>
                        <td>KES {{ number_format((float) $run->total_net, 2) }}</td>
                        <td>{{ ucfirst($run->status) }}</td>
                        <td>{{ $run->gl_reference ?? '-' }}</td>
                        <td>{{ $run->posted_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('finance.payroll-integration.show', array_merge($dept, ['payrollRun' => $run->id])) }}" class="tich-btn tich-btn-ghost">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>@include('partials.states.table-empty', ['colspan' => 9, 'title' => 'No approved payroll runs yet. Approve a run in HR first.', 'icon' => 'inbox'])</tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-4">{{ $runs->links() }}</div>
@endsection
