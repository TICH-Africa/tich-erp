@extends('layouts.finance')

@section('title', 'Fee Structures')

@section('finance-content')
    <x-page-toolbar title="Fee Structures" meta="Define fees applicable to a program, academic year, and semester">
<x-slot:actions>
            <a href="{{ route('finance.student-finance.fee-structures.create', ['department' => $department->id]) }}" class="tich-btn tich-btn-primary">+ New fee structure</a>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                    <th>Program</th>
                        <th>Academic Year</th>
                        <th>Total Fee</th>
                        <th>Status</th>
                        <th>Effective From</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($structures as $structure)
                        <tr>
                            <td><strong>{{ $structure->program->program_name ?? 'N/A' }}</strong></td>
                            <td class="tich-caption">{{ $structure->academicYear->year_label ?? 'N/A' }}</td>
                            <td>KES {{ number_format($structure->total_semester_fee, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $structure->is_active ? 'success' : 'secondary' }}">
                                    {{ $structure->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $structure->effective_from?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.fee-structures.show', ['department' => $department->id, 'id' => $structure->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="tich-table-empty">No fee structures found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($structures instanceof \Illuminate\Contracts\Pagination\Paginator && $structures->hasPages())
            <div class="tich-mt-4">{{ $structures->links() }}</div>
        @endif
    </div>
@endsection



