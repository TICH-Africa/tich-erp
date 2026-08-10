@extends('layouts.finance')

@section('title', 'Edit fee structure')

@section('finance-content')
    <x-page-toolbar title="Edit fee structure" />

    <form method="post" action="{{ route('finance.fee-structures.update', $feeStructure) }}" class="tich-card tich-form-grid">
        @csrf
        @method('PUT')
        @include('finance.fee-structures.partials.form', ['feeStructure' => $feeStructure])
        <div><button type="submit" class="tich-btn tich-btn-primary">Update fee structure</button></div>
    </form>
@endsection
