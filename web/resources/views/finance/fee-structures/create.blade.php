@extends('layouts.finance')

@section('title', 'New fee structure')

@section('finance-content')
    <x-page-toolbar title="New fee structure" meta="Create a new fee structure for a programme">
        <x-slot:actions>
            <a href="{{ route('finance.fee-structures.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="uf-form">
        <form method="POST" action="{{ route('finance.fee-structures.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">FEE · Fee structure</div>
                    <div class="uf-amount-bar__sum">Programme fee configuration</div>
                </div>
                <span class="uf-badge">Draft</span>
            </div>

            @include('finance.fee-structures.partials.form')

            <div class="uf-form-section">
                <div class="uf-section-head">Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Save fee structure</button>
                        <a href="{{ route('finance.fee-structures.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
