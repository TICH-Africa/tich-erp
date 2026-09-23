@extends('layouts.hr')

@section('title', 'Staff Archive')

@section('hr-content')
    <x-page-toolbar title="Staff Archive" meta="Archived and permanently deleted staff records">
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.archive.index') }}" class="tich-page-toolbar__filters-form" data-live-filter>
                @include('partials.search-field', ['placeholder' => 'Search archived staff...', 'value' => request('search')])
                <input type="text" name="reason" class="tich-input tich-input--compact" placeholder="Archive reason" value="{{ request('reason') }}">
            </form>
        </x-slot:filters>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Employee No.</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Archived At</th>
                        <th>Reason</th>
                        <th>Archived By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($archivedStaff as $staff)
                        <tr>
                            <td>{{ $staff->employee_number }}</td>
                            <td>{{ $staff->fullName() }}</td>
                            <td>{{ $staff->department?->dept_name ?? '-' }}</td>
                            <td>{{ $staff->primary_email }}</td>
                            <td>{{ $staff->archived_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>{{ Str::limit($staff->archive_reason, 80) }}</td>
                            <td>{{ $staff->archivedBy?->fullName() ?? '-' }}</td>
                            <td>
                                <div class="tich-archive-actions">
                                    @if ($staff->user && $staff->user->is_active)
                                        <form method="POST" action="{{ route('hr.archive.deactivate', $staff) }}" onsubmit="return confirm('Deactivate this staff user account?');">
                                            @csrf
                                            <button type="submit" class="tich-squircle-btn tich-squircle-btn--warn" title="Deactivate user account" aria-label="Deactivate user account">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <path d="m4.9 4.9 14.2 14.2"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('hr.archive.restore', $staff) }}" onsubmit="return confirm('Restore this staff member?');">
                                        @csrf
                                        <button type="submit" class="tich-squircle-btn tich-squircle-btn--ok" title="Restore staff" aria-label="Restore staff">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                                                <path d="M21 3v5h-5"/>
                                                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                                                <path d="M8 16H3v5"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('hr.archive.destroy', $staff) }}" onsubmit="return confirm('Permanently delete this record? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tich-squircle-btn tich-squircle-btn--danger" title="Permanently delete" aria-label="Permanently delete">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                                                <path d="M10 11v6"/>
                                                <path d="M14 11v6"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 8, 'title' => 'No archived staff found', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $archivedStaff->links() }}
@endsection
