{{-- blade/pages/sacco/dashboard.blade.php
    Route: GET /sacco/dashboard
--}}
@extends('layouts.sacco')

@section('title', 'SACCO Dashboard – TICH ERP')
@section('page_title', 'SACCO Dashboard')

@section('content')
<div class="space-y-5">
    <div class="bg-gradient-to-r from-amber-800 to-amber-700 rounded-xl p-5 text-white mb-2">
        <p class="text-xs text-amber-200 font-medium mb-1">{{ now()->format('l, d F Y') }}</p>
        <h2 class="text-xl font-extrabold">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h2>
        <p class="text-sm text-amber-200 mt-1">SACCO Member Portal</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white border border-gray-100 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-medium">Total Savings</p>
            <p class="text-xl font-extrabold mt-1 text-gray-900">KES 124,000</p>
            <p class="text-xs mt-1 text-green-600">Active contributions</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-medium">Active Loans</p>
            <p class="text-xl font-extrabold mt-1 text-gray-900">1</p>
            <p class="text-xs mt-1 text-amber-600">KSh 300,000 outstanding</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-medium">Next Contribution</p>
            <p class="text-xl font-extrabold mt-1 text-gray-900">Aug 2026</p>
            <p class="text-xs mt-1 text-gray-400">KES 3,000 due</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-medium">Dividends YTD</p>
            <p class="text-xl font-extrabold mt-1 text-gray-900">KES 4,200</p>
            <p class="text-xs mt-1 text-green-600">Paid Jun 2026</p>
        </div>
    </div>
</div>
@endsection
