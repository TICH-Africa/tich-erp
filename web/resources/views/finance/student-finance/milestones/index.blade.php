@extends('layouts.department')

@section('title', 'Payment Milestones')

@section('finance-content')
    <x-page-toolbar title="Payment Milestones" meta="Student fee payment milestones: 50% registration, 75% mid-semester, 100% before final exams">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index', ['department' => $department->id]) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mb-8">
        <form method="get" action="{{ route('finance.student-finance.milestones.index', $department) }}" class="tich-flex tich-items-center tich-gap-2 flex-wrap">
            <div class="tich-flex-1" style="max-width: 260px;">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search student..." class="tich-input pl-8" style="padding: 8px 12px 8px 32px; font-size: 14px;">
                    <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            <select name="semester_id" class="tich-input" style="padding: 8px 12px; font-size: 14px; max-width: 180px;">
                <option value="0">All Semesters</option>
                @foreach ($allSemesters as $sem)
                    <option value="{{ $sem->id }}" {{ $semesterId == $sem->id ? 'selected' : '' }}>{{ $sem->displayLabel() }}</option>
                @endforeach
            </select>
            <select name="academic_year_id" class="tich-input" style="padding: 8px 12px; font-size: 14px; max-width: 180px;">
                <option value="0">All Academic Years</option>
                @foreach ($allAcademicYears as $year)
                    <option value="{{ $year->id }}" {{ $academicYearId == $year->id ? 'selected' : '' }}>{{ $year->year_label ?? $year->year }}</option>
                @endforeach
            </select>
            <button type="submit" class="tich-btn tich-btn-secondary" style="padding: 8px 16px; font-size: 14px;">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1zM3 12a1 1 0 011-1h10a1 1 0 010 2H4a1 1 0 01-1-1zM3 20a1 1 0 011-1h16a1 1 0 010 2H4a1 1 0 01-1-1z"></path>
                </svg>
                Filter
            </button>
            @if ($search !== '' || $semesterId > 0 || $academicYearId > 0)
                <a href="{{ route('finance.student-finance.milestones.index', $department) }}" class="tich-btn tich-btn-ghost" style="padding: 8px 16px; font-size: 14px;">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear
                </a>
            @endif
        </form>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Milestone</th>
                        <th>Percentage</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($milestones as $milestone)
                        <tr>
                            <td>
                                <strong>{{ $milestone->student->fullName() ?? 'N/A' }}</strong>
                                <p class="tich-caption">{{ $milestone->student->registration_number ?? 'N/A' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $milestone->milestone_type)) }}</td>
                            <td>{{ $milestone->percentage }}%</td>
                            <td>KES {{ number_format($milestone->milestone_amount, 2) }}</td>
                            <td>KES {{ number_format($milestone->paid_amount, 2) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($milestone->status) {
                                    'pending' => 'secondary',
                                    'partial' => 'warning',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst($milestone->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $milestone->due_date?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('finance.student-finance.milestones.show', ['department' => $department->id, 'id' => $milestone->id]) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No payment milestones found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($milestones instanceof \Illuminate\Contracts\Pagination\Paginator && $milestones->hasPages())
            <div class="tich-mt-4">{{ $milestones->links() }}</div>
        @endif
    </div>
@endsection


