@extends('layouts.finance')

@section('title', 'Budgeting')

@section('department-content')
    <x-page-toolbar title="Budgeting" meta="Budget details">
        <x-slot:actions>
            <a href="{{ route('finance.budgeting.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <p class="tich-table-empty">Budget details will appear here.</p>
    </div>
@endsection

