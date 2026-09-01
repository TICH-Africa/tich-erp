@extends('layouts.hr')

@section('title', 'Payroll Runs')

@section('hr-content')
    @include('partials.financial-privacy')

    <x-page-toolbar title="Payroll runs" meta="Stored monthly payroll batches with approval and statutory exports">
        <x-slot:actions>
            <a href="{{ route('hr.payroll.index') }}" class="tich-btn tich-btn-secondary">Live preview</a>
            <a href="{{ route('hr.payroll.runs.create') }}" class="tich-btn tich-btn-primary">+ New payroll run</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Run</th>
                    <th>Period</th>
                    <th>Staff</th>
                    <th data-financial-col="gross">Gross</th>
                    <th data-financial-col="net">Net</th>
                    <th>Status</th>
                    <th>Approved</th>
                    <th>Posted to GL</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($runs as $run)
                    <tr>
                        <td><a href="{{ route('hr.payroll.runs.show', $run) }}" class="tich-link">{{ $run->run_number }}</a></td>
                        <td>{{ $run->periodLabel() }}</td>
                        <td>{{ $run->staff_count }}</td>
                        <td data-financial-col="gross"><span class="tich-financial-cell">KES {{ number_format((float) $run->total_gross, 2) }}</span></td>
                        <td data-financial-col="net"><span class="tich-financial-cell">KES {{ number_format((float) $run->total_net, 2) }}</span></td>
                        <td>{{ ucfirst($run->status) }}</td>
                        <td>{{ $run->approved_at?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $run->posted_at?->format('d M Y') ?? '-' }}</td>
                        <td><a href="{{ route('hr.payroll.runs.show', $run) }}" class="tich-btn tich-btn-ghost">Open</a></td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 9, 'title' => 'No payroll runs yet', 'description' => 'Create one to save a monthly batch.', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="tich-mt-4">{{ $runs->links() }}</div>
@endsection
