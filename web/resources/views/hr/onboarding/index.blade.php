@extends('layouts.hr')

@section('title', 'Onboarding')

@section('hr-content')
    <div class="tich-mb-8">
        <h1 class="tich-h1">Onboarding</h1>
        <p class="tich-text tich-mt-2">Track new hire onboarding progress from applicant to active employee.</p>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Onboarding No.</th>
                        <th>Staff</th>
                        <th>Department</th>
                        <th>Step</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($onboardings as $onboarding)
                        <tr>
                            <td>{{ $onboarding->onboarding_number }}</td>
                            <td>
                                <strong>{{ $onboarding->staff->fullName() ?? '—' }}</strong>
                                <p class="tich-caption">{{ $onboarding->staff->employee_number ?? '' }}</p>
                            </td>
                            <td>{{ $onboarding->staff?->department?->dept_name ?? '—' }}</td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $onboarding->current_step)) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $onboarding->status === 'completed' ? 'success' : ($onboarding->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($onboarding->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $onboarding->created_at?->format('Y-m-d') }}</td>
                            <td>
                                @if ($onboarding->staff)
                                    <div class="tich-flex tich-flex--gap">
                                        <a href="{{ route('hr.staff.show', $onboarding->staff) }}" class="tich-btn tich-btn-ghost">View staff</a>
                                        <a href="{{ route('hr.contracts.create', ['staff_id' => $onboarding->staff->id]) }}" class="tich-btn tich-btn-primary">+ Contract</a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No onboarding records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($onboardings->hasPages())
            <div class="tich-mt-6">
                {{ $onboardings->links() }}
            </div>
        @endif
    </div>
@endsection
