@extends('layouts.hr')

@section('title', 'Documents - ' . $staff->fullName())

@section('hr-content')
    <x-page-toolbar :title="$staff->fullName() . ' - Documents'" :meta="$staff->employee_number . ' · ' . $staff->job_title . ' · ' . ($staff->department->dept_name ?? '-')">
        <x-slot:actions>
            <div class="tich-flex tich-flex--gap">
                <a href="{{ route('hr.staff.documents.create', $staff) }}" class="tich-btn tich-btn-primary">+ Upload Document</a>
                <a href="{{ route('hr.staff.documents.send', $staff) }}" class="tich-btn tich-btn-secondary">+ Send Document</a>
            </div>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6">
        @forelse ($staff->documents as $doc)
            <div class="tich-doc-card">
                <div class="tich-doc-card__body">
                    <div class="tich-doc-card__row">
                        <div class="tich-doc-card__icon">
                            <svg class="tich-doc-card__svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-2 0V8m-2 4h2m0 4.01h.01M9 16h6" />
                            </svg>
                        </div>
                        <div class="tich-doc-card__content">
                            <strong class="tich-doc-card__title">{{ $doc->document_name }}</strong>
                            <p class="tich-doc-card__meta">
                                {{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}
                                @if($doc->expiry_date)
                                    · Exp: {{ $doc->expiry_date->format('Y-m-d') }}
                                @endif
                            </p>
                        </div>
                        <span class="tich-badge tich-badge--{{ $doc->status === 'approved' ? 'success' : ($doc->status === 'rejected' ? 'danger' : 'warning') }} tich-badge--sm">
                            {{ ucfirst($doc->status ?? 'pending') }}
                        </span>
                    </div>
                    @if ($doc->notes)
                        <p class="tich-caption tich-mt-1">{{ $doc->notes }}</p>
                    @endif
                    @if ($doc->status === 'rejected')
                        <p class="tich-caption tich-mt-1" style="color: var(--tich-danger, #b91c1c);">
                            Rejected: {{ $doc->rejection_reason }}
                        </p>
                    @endif
                </div>
                <div class="tich-doc-card__actions">
                    <a href="{{ route('hr.staff.documents.read', [$staff, $doc]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">View</a>
                    <a href="{{ route('hr.staff.documents.download', [$staff, $doc]) }}" class="tich-btn tich-btn-ghost tich-btn--sm">Download</a>
                    <form method="POST" action="{{ route('hr.staff.documents.destroy', [$staff, $doc]) }}" onsubmit="return confirm('Delete this document? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tich-btn tich-btn--sm tich-btn--danger">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="tich-text-center tich-mt-6 tich-mb-4" style="grid-column: 1 / -1;">
                <p class="tich-text tich-text--secondary">No documents uploaded for this staff member.</p>
                <a href="{{ route('hr.staff.documents.create', $staff) }}" class="tich-btn tich-btn-primary tich-mt-4">+ Upload Document</a>
            </div>
        @endforelse
    </div>
@endsection
