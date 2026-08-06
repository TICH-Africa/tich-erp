@extends('layouts.hr')

@section('title', 'Training')

@section('hr-content')
    <x-page-toolbar title="Training & Professional Development" meta="Staff training, certifications, and development">
        <x-slot:actions>
            <a href="{{ route('hr.training.create') }}" class="tich-btn tich-btn-primary">+ Add Training</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Staff</th>
                        <th>Training</th>
                        <th>Provider</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trainings as $training)
                        <tr>
                            <td>
                                @if ($training->is_assigned_to_all)
                                    <strong>All Staff</strong>
                                @else
                                    @foreach ($training->assigned_staff_ids as $staffId)
                                        @php $s = \App\Models\Staff::find($staffId); @endphp
                                        @if ($s)
                                            <strong>{{ $s->fullName() }}</strong>
                                            <p class="tich-caption">{{ $s->employee_number }}</p>
                                        @endif
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                <strong>{{ $training->training_name }}</strong>
                                <span class="tich-badge tich-badge--info tich-ml-2">{{ ucfirst($training->training_type) }}</span>
                            </td>
                            <td class="tich-caption">{{ $training->provider }}</td>
                            <td class="tich-caption">{{ $training->start_date?->format('Y-m-d') }}</td>
                            <td class="tich-caption">{{ $training->end_date?->format('Y-m-d') ?? 'Ongoing' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $training->is_completed ? 'success' : 'warning' }}">
                                    {{ $training->is_completed ? 'Completed' : 'In Progress' }}
                                </span>
                            </td>
                            <td>
                                <div class="tich-flex tich-flex--gap">
                                    <a href="{{ route('hr.training.edit', $training) }}" class="tich-btn tich-btn-ghost">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="tich-table-empty">No training records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($trainings->hasPages())
            <div class="tich-mt-6">
                {{ $trainings->links() }}
            </div>
        @endif
    </div>
@endsection
