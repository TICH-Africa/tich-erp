@extends('layouts.app')

@section('title', 'Getting started')

@section('content')
<div class="tich-container" style="max-width: 40rem; margin: 2.5rem auto; padding: 0 1rem;">
    <x-page-toolbar
        title="Welcome to TICH ERP"
        meta="{{ $user->email }}"
    />

    @if ($mustCompleteProfile)
        <div class="tich-alert tich-alert--warning tich-mt-6" role="status">
            <strong>Complete your employee profile first.</strong>
            You need to confirm your contact and emergency details before using other modules.
            @if ($missingProfileLabels)
                <p class="tich-caption tich-mt-2">Still needed: {{ implode(', ', $missingProfileLabels) }}</p>
            @endif
        </div>
        <p class="tich-mt-6">
            <a href="{{ route('employee.profile.edit') }}" class="tich-btn tich-btn-primary">Complete my profile</a>
        </p>
    @elseif ($awaitingDepartmentAssignment ?? false)
        <p class="tich-text tich-mt-6">
            Your profile is ready. Open the main dashboard to browse departments while HR or ICT assigns you to a unit.
        </p>
        <div class="tich-flex tich-mt-6" style="gap: 0.75rem; flex-wrap: wrap;">
            @if ($canOpenDashboard)
                <a href="{{ route('dashboard') }}" class="tich-btn tich-btn-primary">Open department dashboard</a>
            @endif
        </div>
    @elseif ($canOpenEmployeePortal)
        <p class="tich-text tich-mt-6">
            Your account is active. Open My Employee Portal to view your details, or wait for ICT/HR to assign department module access if you need the main dashboard.
        </p>
        <div class="tich-flex tich-mt-6" style="gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('employee.dashboard') }}" class="tich-btn tich-btn-primary">Open employee portal</a>
            @if ($canOpenDashboard)
                <a href="{{ route('dashboard') }}" class="tich-btn tich-btn-secondary">Main dashboard</a>
            @endif
        </div>
    @else
        <div class="tich-alert tich-alert--warning tich-mt-6" role="status">
            <strong>Account created — access not assigned yet.</strong>
            Sign-in works, but no employee record or module roles are linked to this account yet. Ask ICT or HR to link your staff profile and assign a department role.
        </div>
        <p class="tich-caption tich-mt-4">
            After they assign access, refresh this page or sign in again.
        </p>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="tich-mt-8">
        @csrf
        <button type="submit" class="tich-btn tich-btn-ghost">Sign out</button>
    </form>
</div>
@endsection
