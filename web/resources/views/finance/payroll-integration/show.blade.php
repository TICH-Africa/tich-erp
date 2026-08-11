@extends('layouts.finance')

@section('title', 'Payroll Integration')

@section('department-content')
    <x-page-toolbar title="Payroll Integration" meta="Payroll sync details">
        <x-slot:actions>
            <a href="{{ route('finance.payroll-integration.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <p class="tich-table-empty">Payroll integration details will appear here.</p>
    </div>
@endsection

