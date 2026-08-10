@extends('layouts.finance')

@section('title', 'Fee structures')

@section('finance-content')
    <x-page-toolbar title="Fee structures" meta="Programme, year, and semester fee configuration">
        <a href="{{ route('finance.fee-structures.create') }}" class="tich-btn tich-btn-primary">New fee structure</a>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Programme</th>
                    <th>Year</th>
                    <th>Application</th>
                    <th>Semester total</th>
                    <th>QA (annual)</th>
                    <th>Graduation</th>
                    <th>Approved</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($feeStructures as $feeStructure)
                    <tr>
                        <td>{{ $feeStructure->program?->program_name }}</td>
                        <td>{{ $feeStructure->academicYear?->year_label }}</td>
                        <td>KES {{ number_format((float) $feeStructure->application_fee, 0) }}</td>
                        <td>KES {{ number_format((float) $feeStructure->total_semester_fee, 0) }}</td>
                        <td>KES {{ number_format((float) $feeStructure->qa_annual_fee, 0) }}</td>
                        <td>KES {{ number_format((float) $feeStructure->graduation_fee, 0) }}</td>
                        <td>{{ $feeStructure->is_approved ? 'Yes' : 'Pending' }}</td>
                        <td><a href="{{ route('finance.fee-structures.show', $feeStructure) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="tich-caption">No fee structures configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $feeStructures->links() }}
@endsection
