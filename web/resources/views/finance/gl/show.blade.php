@extends('layouts.finance')

@section('title', 'General Ledger')

@section('department-content')
    <x-page-toolbar title="General Ledger" meta="Journal entry details">
        <x-slot:actions>
            <a href="{{ route('finance.gl.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <p class="tich-table-empty">Journal entry details will appear here.</p>
    </div>
@endsection

