@extends('layouts.hr')

@section('title', 'Documents - ' . $staff->fullName())

@section('hr-content')
    <div class="tich-mb-8">
        <div class="tich-flex tich-flex--between tich-flex--start">
            <div>
                <a href="{{ route('hr.documents.index') }}" class="tich-btn tich-btn-ghost">&larr; Back to staff documents</a>
                <h1 class="tich-h1 tich-mt-4">{{ $staff->fullName() }} - Documents</h1>
                <p class="tich-text tich-mt-2">{{ $staff->employee_number }} · {{ $staff->job_title }} · {{ $staff->department->dept_name ?? '—' }}</p>
            </div>
            <div class="tich-flex tich-flex--gap">
                <a href="{{ route('hr.staff.documents.create', $staff) }}" class="tich-btn tich-btn-primary">+ Upload Document</a>
                <a href="{{ route('hr.staff.documents.send', $staff) }}" class="tich-btn tich-btn-secondary">+ Send Document</a>
            </div>
        </div>
    </div>

    <div class="tich-card tich-table-panel">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Document Name</th>
                        <th>File</th>
                        <th>Issue Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff->documents as $doc)
                        <tr>
                            <td class="tich-caption">{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</td>
                            <td>
                                <strong>{{ $doc->document_name }}</strong>
                                @if ($doc->notes)
                                    <p class="tich-caption tich-mt-1">{{ $doc->notes }}</p>
                                @endif
                            </td>
                            <td class="tich-caption">{{ $doc->original_filename }}</td>
                            <td class="tich-caption">{{ $doc->issue_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="tich-caption">{{ $doc->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                            <td>
                                <span class="tich-badge tich-badge--{{ $doc->is_verified ? 'success' : 'warning' }}">
                                    {{ $doc->is_verified ? 'Verified' : 'Pending' }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $doc->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="tich-flex tich-flex--gap">
                                    <a href="{{ route('hr.staff.documents.download', [$staff, $doc]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Download</a>
                                    <form method="POST" action="{{ route('hr.staff.documents.destroy', [$staff, $doc]) }}" onsubmit="return confirm('Delete this document?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tich-btn tich-btn-ghost tich-btn--sm" style="color: #c53030; border-color: #c53030;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="tich-table-empty">No documents uploaded for this staff member.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
