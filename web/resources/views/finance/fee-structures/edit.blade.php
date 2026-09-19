@extends('layouts.finance')

@section('title', 'Edit fee structure')

@section('finance-content')
    <x-page-toolbar title="Edit fee structure">
        <x-slot:actions>
            <a href="{{ route('finance.fee-structures.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="post" action="{{ route('finance.fee-structures.update', $feeStructure) }}" data-uf="ready">
            @csrf
            @method('PUT')

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">FEE · Fee structure</div>
                    <div class="uf-amount-bar__sum">{{ $feeStructure->program?->program_name ?? 'Edit fee structure' }}</div>
                </div>
                <span class="uf-badge">Edit</span>
            </div>

            @include('finance.fee-structures.partials.form', ['feeStructure' => $feeStructure])

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Update fee structure</button>
                        <a href="{{ route('finance.fee-structures.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
