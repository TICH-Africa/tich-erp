@extends('layouts.administration')

@section('title', 'Applications for ' . $financialAidOpportunity->title)

@section('administration-content')
    <x-page-toolbar title="Applications" meta="{{ $financialAidOpportunity->title }} - {{ $applications->total() }} applications">
        <x-slot:actions>
            <a href="{{ route('administration.financial-aid.show', $financialAidOpportunity) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($applications->isEmpty())
        <div class="tich-card tich-mt-6">
            <div class="tich-card__body" style="text-align: center; padding: 3rem;">
                <p class="tich-text tich-text--muted">No applications received yet.</p>
            </div>
        </div>
    @else
        <div class="tich-card tich-mt-6">
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td>
                                    <strong>{{ $application->student_name }}</strong>
                                    @if ($application->student_number)
                                        <br><small class="tich-text--muted">ID: {{ $application->student_number }}</small>
                                    @endif
                                </td>
                                <td>{{ $application->student_email }}</td>
                                <td>{{ $application->program_applied ?? '-' }}</td>
                                <td>{{ $application->created_at->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'under_review' => 'bg-blue-100 text-blue-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'allocated' => 'bg-purple-100 text-purple-800',
                                        ];
                                    @endphp
                                    <span class="tich-badge {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('administration.financial-aid.review', $application) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $applications->links() }}
        </div>
    @endif
@endsection