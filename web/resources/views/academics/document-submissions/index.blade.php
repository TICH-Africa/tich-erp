@extends('layouts.academics')

@section('academics-content')
    @php $hub = \App\Support\AcademicsRouteParams::for(['learning_department' => request()->integer('learning_department') ?: null]); @endphp

    <x-page-toolbar title="Document change requests" meta="Student replacements awaiting registrar approval">
        <x-slot:actions>
            <a href="{{ route('departments.academics.document-requests.index', $hub) }}" class="tich-btn tich-btn-secondary">Issued document requests</a>
            <a href="{{ route('departments.academics.dashboard', $hub) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mb-8">
        <form method="GET" action="{{ route('departments.academics.document-submissions.index', $hub) }}" class="tich-flex-wrap" style="gap:0.75rem; align-items:end;">
            <div class="tich-form-group" style="margin:0;">
                <label for="status" class="tich-label">Status</label>
                <select id="status" name="status" class="tich-select">
                    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
                        <option value="{{ $value }}" @selected(($status ?? 'pending') === $value)>{{ $label }}</option>
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
                        <th>Document</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($submissions as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->student?->fullName() ?? '-' }}</strong>
                                <p class="tich-caption">{{ $item->student?->registration_number ?? '-' }}</p>
                            </td>
                            <td>{{ $item->typeLabel() }}</td>
                            <td>{{ ucfirst($item->status) }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                <a href="{{ route('departments.academics.document-submissions.show', array_merge($hub, ['submission' => $item->id])) }}" class="tich-btn tich-btn-ghost">Review</a>
                            </td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 5, 'title' => 'No document change requests', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($submissions, 'hasPages') && $submissions->hasPages())
            <div class="tich-mt-4">{{ $submissions->links() }}</div>
        @endif
    </div>
@endsection
