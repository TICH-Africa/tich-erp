@extends('layouts.hr')

@section('title', 'Employee Relations - Disciplinary')

@section('hr-content')
    <x-page-toolbar title="Disciplinary cases" meta="Track misconduct investigations, hearings, and outcomes">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="disciplinary-create-modal">+ New case</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Case no.</th>
                        <th>Employee</th>
                        <th>Incident date</th>
                        <th>Status</th>
                        <th>Assigned to</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cases as $case)
                        <tr>
                            <td><strong>{{ $case->case_number }}</strong></td>
                            <td>
                                <strong>{{ $case->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $case->staff->employee_number }}</p>
                            </td>
                            <td class="tich-caption">{{ $case->incident_date->format('d M Y') }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($case->status) {
                                    'open' => 'warning',
                                    'under_investigation' => 'info',
                                    'hearing_scheduled' => 'info',
                                    'decided' => 'success',
                                    'appealed' => 'warning',
                                    'closed' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $case->assignedTo?->fullName() ?? '-' }}</td>
                            <td>
                                <a href="{{ route('hr.employee-relations.disciplinary.show', $case) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No disciplinary cases found', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cases->hasPages())
            <div class="tich-mt-4">{{ $cases->links() }}</div>
        @endif
    </div>

    @include('hr.disciplinary.partials.create-modal')
@endsection

@section('scripts')
    @parent
    @include('admin.partials.tich-modal-assets')
    @if ($openCreateModal ?? false)
        <script>document.body.style.overflow = 'hidden';</script>
    @endif
@endsection
