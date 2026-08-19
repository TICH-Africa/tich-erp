@extends('layouts.finance')

@section('title', 'New fee structure')

@section('finance-content')
    <x-page-toolbar title="New fee structure" meta="Create a new fee structure for a programme">
        <x-slot:actions>
            <a href="{{ route('finance.fee-structures.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('finance.fee-structures.store') }}" class="tich-card tich-form-grid">
        @csrf
        @include('finance.fee-structures.partials.form')
        <div class="tich-form-row">
            <button type="submit" class="tich-btn tich-btn-primary">Save fee structure</button>
            <a href="{{ route('finance.fee-structures.index') }}" class="tich-btn tich-btn-ghost" style="margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>
@endsection
