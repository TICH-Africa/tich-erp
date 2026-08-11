@extends('layouts.finance')

@section('title', 'Accounts Payable')

@section('department-content')
    <x-page-toolbar title="Accounts Payable" meta="Invoice details">
        <x-slot:actions>
            <a href="{{ route('finance.ap.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <p class="tich-table-empty">Invoice details will appear here.</p>
    </div>
@endsection

