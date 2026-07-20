@extends('layouts.admin')

@section('title', 'Platform administration')

@section('admin-content')
    <div class="tich-mb-8">
        <h1 class="tich-h1" style="font-size: 2rem;">Platform administration</h1>
        <p class="tich-text tich-mt-2">Configure campuses, departments, user roles, and dashboard access across TICH ERP.</p>
    </div>

    <div class="tich-grid tich-grid--4 tich-mb-8">
        <div class="tich-stat"><p class="tich-stat__label">Active campuses</p><p class="tich-stat__value">{{ $stats['campuses'] }}</p></div>
        <div class="tich-stat"><p class="tich-stat__label">Departments</p><p class="tich-stat__value">{{ $stats['departments'] }}</p></div>
        <div class="tich-stat"><p class="tich-stat__label">Active users</p><p class="tich-stat__value">{{ $stats['users'] }}</p></div>
        <div class="tich-stat"><p class="tich-stat__label">Roles</p><p class="tich-stat__value">{{ $stats['roles'] }}</p></div>
    </div>

    <div class="tich-grid tich-grid--3">
        @can('campuses.manage')
        <article class="tich-card">
            <h3 class="tich-h3">Campuses</h3>
            <p class="tich-text">Create and manage main campus, community colleges, and sub-county hubs.</p>
            <a href="{{ route('admin.campuses.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Manage campuses</a>
        </article>
        @endcan

        @can('departments.manage')
        <article class="tich-card">
            <h3 class="tich-h3">Departments</h3>
            <p class="tich-text">Academic, administrative, and support departments per campus.</p>
            <a href="{{ route('admin.departments.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Manage departments</a>
        </article>
        @endcan

        @can('users.access.manage')
        <article class="tich-card">
            <h3 class="tich-h3">Users &amp; access</h3>
            <p class="tich-text">Assign roles, scope by campus/department, and control dashboard modules.</p>
            <a href="{{ route('admin.users.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Manage users</a>
        </article>
        @endcan
    </div>

    <div class="tich-card tich-mt-8">
        <h3 class="tich-h3">Platform modules registry</h3>
        <p class="tich-text tich-mb-4">Dashboard areas that can be granted to users via roles or direct assignment.</p>
        <div class="tich-grid tich-grid--2">
            @foreach ($modules as $module)
                <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--tich-neutral-border);">
                    <strong>{{ $module['label'] }}</strong>
                    @if (!empty($module['coming_soon']))
                        <span class="tich-caption"> · Coming soon</span>
                    @endif
                    <p class="tich-caption tich-mt-1">{{ $module['description'] }}</p>
                    <p class="tich-caption">Permission: {{ $module['permission'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
