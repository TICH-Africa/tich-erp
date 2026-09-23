@extends('layouts.hr')

@section('title', 'Staff Archive')

@section('hr-content')
    <x-page-toolbar title="Staff Archive" meta="Archived and permanently deleted staff records">
        <x-slot:filters>
            <form method="GET" action="{{ route('hr.archive.index') }}" class="tich-page-toolbar__filters-form">
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
                                @if ($staff->user?->is_active !== false)
                                    <form method="POST" action="{{ route('hr.archive.deactivate', $staff) }}" onsubmit="return confirm('Deactivate this staff user account?');" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="tich-squircle-btn" title="Deactivate user">⊘</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('hr.archive.restore', $staff) }}" onsubmit="return confirm('Restore this staff member?');" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="tich-squircle-btn" title="Restore">↺</button>
                                </form>
                                <form method="POST" action="{{ route('hr.archive.destroy', $staff) }}" onsubmit="return confirm('Permanently delete this record? This cannot be undone.');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tich-squircle-btn" title="Permanently delete">✕</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No archived staff found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $archivedStaff->links() }}
@endsection
