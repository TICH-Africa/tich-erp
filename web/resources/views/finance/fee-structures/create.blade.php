@extends('layouts.finance')

@section('title', 'New fee structure')

@section('finance-content')
    <x-page-toolbar title="New fee structure" />

    <form method="post" action="{{ route('finance.fee-structures.store') }}" class="tich-card tich-form-grid">
        @csrf
        @include('finance.fee-structures.partials.form')
        <div><button type="submit" class="tich-btn tich-btn-primary">Save fee structure</button></div>
    </form>
@endsection
