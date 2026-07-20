@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <div class="tich-mb-8">
                <h1 class="tich-h1">Welcome, {{ auth()->user()->username }}</h1>
                <p class="tich-text tich-mt-2">
                    Signed in as <span class="tich-caption">{{ ucfirst(auth()->user()->user_type) }}</span>
                    @if (auth()->user()->roles->isNotEmpty())
                        · Roles:
                        @foreach (auth()->user()->roles as $role)
                            <span class="tich-caption">{{ $role->role_name }}</span>@if (! $loop->last), @endif
                        @endforeach
                    @endif
                </p>
            </div>

            <div class="tich-grid tich-grid--3">
                @can('admin.access')
                <article class="tich-card tich-card--highlight">
                    <h3 class="tich-h3">Platform administration</h3>
                    <p class="tich-text">Campuses, departments, users, roles, and module access.</p>
                    <a href="{{ route('admin.index') }}" class="tich-btn tich-btn-primary tich-mt-4">Open admin panel</a>
                </article>
                @endcan

                @foreach ($modules as $module)
                    @if ($module['key'] === 'admin_hub')
                        @continue
                    @endif
                    <article class="tich-card">
                        <p class="tich-caption">{{ ucfirst($module['category'] ?? 'module') }}</p>
                        <h3 class="tich-h3 tich-mt-2">{{ $module['label'] }}</h3>
                        <p class="tich-text tich-mt-2">{{ $module['description'] }}</p>
                        @if (!empty($module['coming_soon']))
                            <p class="tich-caption tich-mt-4">Coming soon</p>
                        @elseif (!empty($module['route']))
                            <a href="{{ route($module['route']) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open</a>
                        @endif
                    </article>
                @endforeach

                <article class="tich-card">
                    <h3 class="tich-h3">Security</h3>
                    <p class="tich-text">
                        MFA:
                        @if (auth()->user()->mfa_enabled)
                            <span class="tich-caption">Enabled ({{ str_replace('_', ' ', auth()->user()->mfa_method) }})</span>
                        @else
                            <span class="tich-caption">Not configured</span>
                        @endif
                    </p>
                    @unless (auth()->user()->mfa_enabled)
                        <a href="{{ route('mfa.setup') }}" class="tich-btn tich-btn-secondary tich-mt-4">Set up MFA</a>
                    @endunless
                </article>

                <article class="tich-card">
                    <h3 class="tich-h3">Account</h3>
                    <p class="tich-text">Sign out securely from this device.</p>
                    <form method="POST" action="{{ route('logout') }}" class="tich-mt-4">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-secondary">Sign out</button>
                    </form>
                </article>
            </div>
        </div>
    </section>
@endsection
