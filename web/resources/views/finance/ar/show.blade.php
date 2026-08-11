@extends('layouts.finance')

@section('title', 'Accounts Receivable')

@section('department-content')
    <x-page-toolbar title="Accounts Receivable" meta="Invoice details">
        <x-slot:actions>
            <a href="{{ route('finance.ar.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <p class="tich-table-empty">Invoice details will appear here.</p>
    </div>
@endsection

