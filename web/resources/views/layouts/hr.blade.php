@extends('layouts.app')

@section('title', 'HR')

@section('content')
<div class="tich-hr-layout">
    <aside class="tich-hr-sidebar">
        <div class="tich-hr-sidebar__header">
            <h2>HR Module</h2>
            <p>Human Resources</p>
        </div>
        <nav class="tich-hr-sidebar__nav">
            <a href="{{ route('hr.dashboard') }}" class="{{ request()->routeIs('hr.dashboard') ? 'is-active' : '' }}">Dashboard</a>
            <a href="{{ route('hr.staff.index') }}" class="{{ request()->routeIs('hr.staff.*') ? 'is-active' : '' }}">Staff Directory</a>
            <a href="{{ route('hr.onboarding.index') }}" class="{{ request()->routeIs('hr.onboarding.*') ? 'is-active' : '' }}">Onboarding</a>
            <a href="{{ route('hr.contracts.index') }}" class="{{ request()->routeIs('hr.contracts.*') ? 'is-active' : '' }}">Contracts</a>
            <a href="{{ route('hr.vacancies.index') }}" class="{{ request()->routeIs('hr.vacancies.*') ? 'is-active' : '' }}">Vacancies</a>
        </nav>
        <div class="tich-hr-sidebar__footer">
            <a href="{{ route('dashboard') }}" class="tich-btn tich-btn-ghost tich-btn--sm">← Back to dashboard</a>
        </div>
    </aside>

    <main class="tich-hr-main">
        @if (session('success'))
            <div class="tich-alert tich-alert--success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="tich-alert tich-alert--error">
                {{ session('error') }}
            </div>
        @endif

        @yield('hr-content')
    </main>
</div>

<style>
    .tich-hr-layout {
        display: flex;
        min-height: 100vh;
        background: #f3f4f6;
    }
    .tich-hr-sidebar {
        width: 260px;
        background: #ffffff;
        border-right: 1px solid #e5e7eb;
        color: #111827;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
    }
    .tich-hr-sidebar__header {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .tich-hr-sidebar__header h2 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
    }
    .tich-hr-sidebar__header p {
        margin: 0.25rem 0 0;
        font-size: 0.875rem;
        color: #6b7280;
    }
    .tich-hr-sidebar__nav {
        flex: 1;
        padding: 1rem 0;
    }
    .tich-hr-sidebar__nav a {
        display: block;
        padding: 0.75rem 1.5rem;
        color: #374151;
        text-decoration: none;
        font-size: 0.95rem;
        transition: all 0.15s ease;
        border-left: 3px solid transparent;
    }
    .tich-hr-sidebar__nav a:hover {
        background: #f3f4f6;
        color: #111827;
    }
    .tich-hr-sidebar__nav a.is-active {
        background: #eff6ff;
        color: #1d4ed8;
        border-left-color: #1d4ed8;
        font-weight: 500;
    }
    .tich-hr-sidebar__footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
    }
    .tich-hr-main {
        flex: 1;
        margin-left: 260px;
        padding: 2rem;
        min-height: 100vh;
    }
    .tich-btn--sm {
        padding: 0.4rem 0.75rem;
        font-size: 0.875rem;
    }
</style>
@endsection
