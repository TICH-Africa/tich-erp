@extends('layouts.finance')

@section('title', 'Fee structure')

@section('finance-content')
    <x-page-toolbar title="Fee structure" meta="{{ $feeStructure->program?->program_name }} — {{ $feeStructure->academicYear?->year_label }}">
        @unless ($feeStructure->is_approved)
            <form method="post" action="{{ route('finance.fee-structures.approve', $feeStructure) }}">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
            </form>
        @endunless
        <a href="{{ route('finance.fee-structures.edit', $feeStructure) }}" class="tich-btn tich-btn-ghost">Edit</a>
    </x-page-toolbar>

    <article class="tich-card">
        <p><strong>Semester:</strong> {{ $feeStructure->semester_number }}</p>
        <p><strong>Total semester fee:</strong> KES {{ number_format((float) $feeStructure->total_semester_fee, 2) }}</p>
        <p><strong>Effective from:</strong> {{ $feeStructure->effective_from?->format('d M Y') }}</p>
        <p><strong>Status:</strong> {{ $feeStructure->is_approved ? 'Approved' : 'Pending approval' }}</p>
    </article>
@endsection
