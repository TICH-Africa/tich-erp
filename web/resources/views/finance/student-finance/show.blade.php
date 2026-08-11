@extends('layouts.finance')

@section('title', 'Student Finance')

@section('finance-content')
    <x-page-toolbar title="Student Finance" meta="Invoice details">
        <x-slot:actions>
            <a href="{{ route('finance.student-finance.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <p class="tich-table-empty">Invoice details will appear here.</p>
    </div>
@endsection



