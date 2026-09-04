@extends('layouts.academics')

@section('academics-content')
    @php
        $hub = \App\Support\AcademicsRouteParams::for([
            'learning_department' => request()->integer('learning_department') ?: null,
        ]);
    @endphp

    <x-page-toolbar title="Profile approvals" meta="Student biodata and photo change requests">
        <x-slot:actions>
            <a href="{{ route('departments.academics.dashboard', $hub) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mb-8">
        <form method="GET" action="{{ route('departments.academics.profile-changes.index', $hub) }}" class="tich-flex-wrap" style="gap:0.75rem; align-items:end;">
            <div class="tich-form-group" style="margin:0;">
                <label for="status" class="tich-label">Status</label>
                <select id="status" name="status" class="tich-select">
                    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="tich-btn tich-btn-secondary">Filter</button>
        </form>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->student?->fullName() ?? '-' }}</strong>
                                <p class="tich-caption">{{ $item->student?->registration_number ?? '-' }}</p>
                            </td>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $item->request_type)) }}</td>
                            <td>{{ ucfirst($item->status) }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('departments.academics.profile-changes.show', array_merge($hub, ['profileChange' => $item->id])) }}" class="tich-btn tich-btn-ghost">View</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No profile change requests', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($requests, 'hasPages') && $requests->hasPages())
            <div class="tich-mt-4">{{ $requests->links() }}</div>
        @endif
    </div>
@endsection
