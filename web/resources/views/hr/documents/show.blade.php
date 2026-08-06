@extends('layouts.hr')

@section('title', 'Documents - ' . $staff->fullName())

@section('hr-content')
    <x-page-toolbar :title="$staff->fullName() . ' — Documents'" :meta="$staff->employee_number . ' · ' . $staff->job_title . ' · ' . ($staff->department->dept_name ?? '—')">
        <x-slot:actions>
            <div class="tich-flex tich-flex--gap">
                <a href="{{ route('hr.staff.documents.create', $staff) }}" class="tich-btn tich-btn-primary">+ Upload Document</a>
                <a href="{{ route('hr.staff.documents.send', $staff) }}" class="tich-btn tich-btn-secondary">+ Send Document</a>
            </div>
        </x-slot:actions>
    </x-page-toolbar>

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
                                <span class="tich-badge tich-badge--{{ $doc->status === 'approved' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($doc->status ?? 'pending') }}
                                </span>
                            </td>
                            <td class="tich-caption">{{ $doc->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="tich-flex tich-flex--gap">
                                    <a href="{{ route('hr.staff.documents.read', [$staff, $doc]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                                </div>
                                @if ($doc->status === 'rejected')
                                    <p class="tich-caption tich-mt-1" style="color: var(--tich-danger, #b91c1c);">
                                        Rejected: {{ $doc->rejection_reason }}
                                    </p>
                                @endif
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
