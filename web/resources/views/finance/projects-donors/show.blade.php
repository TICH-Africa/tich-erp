@extends('layouts.finance')

@section('title', 'Projects & Donors')

@section('department-content')
    <x-page-toolbar title="Projects & Donors" meta="Project details">
        <x-slot:actions>
            <a href="{{ route('finance.projects-donors.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <p class="tich-table-empty">Project details will appear here.</p>
    </div>
@endsection

