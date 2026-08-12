@extends('layouts.hr')

@section('title', 'Employee Relations - Grievances')

@section('hr-content')
    <x-page-toolbar title="Grievances" meta="Workplace concerns raised by staff through HR or the employee portal">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="grievance-create-modal">+ New grievance</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Employee</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Assigned to</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grievances as $grievance)
                        <tr>
                            <td><strong>{{ $grievance->reference_number ?? '#'.$grievance->id }}</strong></td>
                            <td>
                                <strong>{{ $grievance->staff->fullName() }}</strong>
                                <p class="tich-caption">{{ $grievance->staff->employee_number }}</p>
                            </td>
                            <td>{{ $grievance->subject ?? \Illuminate\Support\Str::limit($grievance->description, 50) }}</td>
                            <td class="tich-caption">{{ $grievance->categoryLabel() }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ match($grievance->status) {
                                    'open' => 'warning',
                                    'under_review' => 'info',
                                    'resolved' => 'success',
                                    'closed' => 'secondary',
                                    default => 'secondary',
                                } }}">
                                    {{ ucfirst(str_replace('_', ' ', $grievance->status)) }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $grievance->assignedTo?->fullName() ?? '-' }}</td>
                            <td class="tich-caption">{{ $grievance->created_at?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('hr.employee-relations.grievances.show', $grievance) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No grievances found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($grievances->hasPages())
            <div class="tich-mt-4">{{ $grievances->links() }}</div>
        @endif
    </div>

    @include('hr.grievances.partials.create-modal')
@endsection

@section('scripts')
    @parent
    @include('admin.partials.tich-modal-assets')
    @if ($openCreateModal ?? false)
        <script>document.body.style.overflow = 'hidden';</script>
    @endif
@endsection
