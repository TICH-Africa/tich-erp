<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">HR Module</p>
    <nav class="tich-admin-sidebar__nav">
        <a href="{{ route('hr.dashboard') }}" class="{{ request()->routeIs('hr.dashboard') ? 'is-active' : '' }}">Dashboard</a>
        <a href="{{ route('hr.staff.index') }}" class="{{ request()->routeIs('hr.staff.*') ? 'is-active' : '' }}">Staff Directory</a>
        <a href="{{ route('hr.onboarding.index') }}" class="{{ request()->routeIs('hr.onboarding.*') ? 'is-active' : '' }}">Onboarding</a>
        <a href="{{ route('hr.contracts.index') }}" class="{{ request()->routeIs('hr.contracts.*') ? 'is-active' : '' }}">Contracts</a>
        <a href="{{ route('hr.vacancies.index') }}" class="{{ request()->routeIs('hr.vacancies.*') ? 'is-active' : '' }}">Vacancies</a>
        <a href="{{ route('dashboard') }}">← Back to dashboard</a>
    </nav>
</aside>
