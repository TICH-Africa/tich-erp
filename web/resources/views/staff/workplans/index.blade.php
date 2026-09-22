@extends('layouts.staff')

@section('staff-content')
    <x-page-toolbar title="Semester workplans" :meta="$staff->department?->dept_name">
        <x-slot:actions>
            <a href="{{ route('staff.workplans.create') }}" class="tich-btn tich-btn-primary">New workplan</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Number</th>
                        <th>Title</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Registrar</th>
                        <th>QA</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($workplans as $plan)
                        <tr>
                            <td>{{ $plan->workplan_number }}</td>
                            <td>{{ $plan->title }}</td>
                            <td>{{ $plan->semester?->displayLabel() ?? '-' }}</td>
                            <td><x-status-badge :status="$plan->status" /></td>
                            <td>
                                @if ($plan->registrar_status === 'approved')
                                    <span class="tich-badge tich-badge--success">✓</span>
                                @elseif ($plan->registrar_status === 'pending')
                                    <span class="tich-badge tich-badge--info">pending</span>
                                @else
                                    <x-status-badge :status="$plan->registrar_status" />
                                @endif
                            </td>
                            <td>
                                @if ($plan->qa_status === 'approved')
                                    <span class="tich-badge tich-badge--success">✓</span>
                                @elseif ($plan->qa_status === 'pending')
                                    <span class="tich-badge tich-badge--info">pending</span>
                                @else
                                    <x-status-badge :status="$plan->qa_status" />
                                @endif
                            </td>
                            <td class="tich-table-actions">
                                <a href="{{ route('staff.workplans.show', $plan) }}" class="tich-link">Open</a>
                                @if ($plan->isEditableByHod())
                                    · <a href="{{ route('staff.workplans.edit', $plan) }}" class="tich-link">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="tich-text">No semester workplans yet. Create one for the current semester.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
