@extends('layouts.finance')

@section('title', 'Fee structure')

@section('finance-content')
    <x-page-toolbar title="Fee structure" meta="{{ $feeStructure->program?->program_name }} - {{ $feeStructure->academicYear?->year_label }}">
        <x-slot:actions>
            @unless ($feeStructure->is_approved)
                <form method="post" action="{{ route('finance.fee-structures.approve', $feeStructure) }}">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                </form>
            @endunless
            <a href="{{ route('finance.fee-structures.edit', $feeStructure) }}" class="tich-btn tich-btn-ghost">Edit</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--2">
        <article class="tich-card">
            <h2 class="tich-h3">Application fee</h2>
            <p class="tich-caption">Paid once after application approval</p>
            <p class="tich-stat__value" style="font-size:1.25rem;">KES {{ number_format((float) $feeStructure->application_fee, 2) }}</p>
        </article>
        <article class="tich-card">
            <h2 class="tich-h3">Semester charges (mandatory)</h2>
            <p class="tich-caption">Billed each semester</p>
            <p class="tich-stat__value" style="font-size:1.25rem;">KES {{ number_format((float) $feeStructure->total_semester_fee, 2) }}</p>
            <ul class="tich-caption tich-mt-4">
                @foreach ($feeStructure->semesterChargeLines(false) as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
            @if ($feeStructure->transport_fee > 0 || $feeStructure->accommodation_fee > 0)
                <p class="tich-caption tich-mt-4"><strong>Optional add-ons:</strong></p>
                <ul class="tich-caption">
                    @foreach ($feeStructure->semesterChargeLines(true) as $line)
                        @if (str_contains($line, 'optional'))
                            <li>{{ $line }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </article>
        <article class="tich-card">
            <h2 class="tich-h3">Yearly charges</h2>
            <p>Quality assurance (annual): <strong>KES {{ number_format((float) $feeStructure->qa_annual_fee, 2) }}</strong></p>
        </article>
        <article class="tich-card">
            <h2 class="tich-h3">Once per programme</h2>
            @if ($feeStructure->requires_indexing_nck)
                <p>Indexing (NCK): <strong>KES {{ number_format((float) $feeStructure->indexing_nck_fee, 2) }}</strong></p>
            @else
                <p class="tich-caption">Indexing (NCK) not applicable for this programme.</p>
            @endif
        </article>
        <article class="tich-card">
            <h2 class="tich-h3">Post learning</h2>
            <p>Graduation fees: <strong>KES {{ number_format((float) $feeStructure->graduation_fee, 2) }}</strong></p>
        </article>
        <article class="tich-card">
            <p><strong>Effective from:</strong> {{ $feeStructure->effective_from?->format('d M Y') }}</p>
            <p><strong>Status:</strong> {{ $feeStructure->is_approved ? 'Approved' : 'Pending approval' }}</p>
        </article>
    </div>
@endsection
