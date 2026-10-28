@extends('layouts.finance')

@section('title', 'Time log · '.$log->log_code)

@section('finance-content')
    <x-page-toolbar title="STAFF WEEKLY TIME LOG" meta="{{ $log->log_code }} · {{ $log->week_ref }}">
        <x-slot:actions>
            <a href="{{ route('finance.time-logs.print', $log) }}" class="tich-btn tich-btn-secondary">Print</a>
            <a href="{{ route('finance.time-logs.pdf', $log) }}" class="tich-btn tich-btn-primary">Download PDF</a>
            <a href="{{ route('finance.time-logs.index') }}" class="tich-btn tich-btn-ghost">Back to submitted logs</a>
        </x-slot:actions>
    </x-page-toolbar>

    <p class="tich-caption tich-mt-4">Status: <x-status-badge :status="$log->status" /> · Read only - Finance cannot endorse, approve or change a time log.</p>

    @include('partials.weekly-time-log-form', ['log' => $log, 'departments' => $departments, 'editable' => false])
@endsection
