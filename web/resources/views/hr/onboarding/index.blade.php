@extends('layouts.hr')

@section('title', 'Onboarding')

@section('hr-content')
    <x-page-toolbar title="Onboarding" meta="New hire progress from applicant to active employee" />

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Onboarding No.</th>
                        <th>Photo</th>
                        <th>Staff</th>
                        <th>Department</th>
                        <th>Step</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Documents</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($onboardings as $onboarding)
                        <tr>
                            <td>{{ $onboarding->onboarding_number }}</td>
                            <td>
                                @if ($onboarding->staff)
                                    @include('hr.staff.partials.table-avatar', ['member' => $onboarding->staff])
                                @else
                                    <span class="tich-caption">-</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $onboarding->staff->fullName() ?? '-' }}</strong>
                                <p class="tich-caption">{{ $onboarding->staff->employee_number ?? '' }}</p>
                            </td>
                            <td>{{ $onboarding->staff?->department?->dept_name ?? '-' }}</td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $onboarding->current_step)) }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $onboarding->status === 'completed' ? 'success' : ($onboarding->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($onboarding->status) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $onboarding->created_at?->format('Y-m-d') }}</td>
                            <td class="tich-caption">
                                @if ($onboarding->staff)
                                    {{ $onboarding->staff->documents->count() }} uploaded
                                @endif
                            </td>
                            <td>
                                @if ($onboarding->staff)
                                    <a href="{{ route('hr.contracts.create', ['staff_id' => $onboarding->staff->id]) }}" class="tich-btn tich-btn-primary">+ Contract</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 9, 'title' => 'No onboarding records found', 'icon' => 'inbox'])
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
