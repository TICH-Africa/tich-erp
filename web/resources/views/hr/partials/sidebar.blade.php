<aside class="tich-admin-sidebar">
    <p class="tich-admin-sidebar__title">HR Module</p>
    <nav class="tich-admin-sidebar__nav">
        <a href="{{ route('hr.dashboard') }}" class="{{ request()->routeIs('hr.dashboard') ? 'is-active' : '' }}">Dashboard</a>
        <a href="{{ route('hr.staff.index') }}" class="{{ request()->routeIs('hr.staff.*') ? 'is-active' : '' }}">Staff Directory</a>
        <a href="{{ route('hr.onboarding.index') }}" class="{{ request()->routeIs('hr.onboarding.*') ? 'is-active' : '' }}">Onboarding</a>
        <a href="{{ route('hr.contracts.index') }}" class="{{ request()->routeIs('hr.contracts.*') ? 'is-active' : '' }}">Contracts</a>
        <a href="{{ route('hr.vacancies.index') }}" class="{{ request()->routeIs('hr.vacancies.*') ? 'is-active' : '' }}">Vacancies</a>
        <a href="{{ route('hr.recruitment.index') }}" class="{{ request()->routeIs('hr.recruitment.*') ? 'is-active' : '' }}">Recruitment</a>
        <a href="{{ route('hr.leave.index') }}" class="{{ request()->routeIs('hr.leave.*') ? 'is-active' : '' }}">Leave requests</a>
        <a href="{{ route('hr.payroll.index') }}" class="{{ request()->routeIs('hr.payroll.*') ? 'is-active' : '' }}">Payroll</a>
        <a href="{{ route('hr.policies.index') }}" class="{{ request()->routeIs('hr.policies.*') ? 'is-active' : '' }}">HR Policies</a>
        <a href="{{ route('hr.documents.index') }}" class="{{ request()->routeIs('hr.documents.index') || request()->routeIs('hr.documents.show') || request()->routeIs('hr.staff.documents.*') ? 'is-active' : '' }}">Staff Documents</a>
        <a href="{{ route('hr.documents.templates.index') }}" class="{{ request()->routeIs('hr.documents.templates.*') ? 'is-active' : '' }}">Document Templates</a>
        <a href="{{ route('hr.offboarding.index') }}" class="{{ request()->routeIs('hr.offboarding.*') ? 'is-active' : '' }}">Offboarding</a>
        <a href="{{ route('hr.training.index') }}" class="{{ request()->routeIs('hr.training.*') ? 'is-active' : '' }}">Training</a>
        <a href="{{ route('dashboard') }}">← Back to dashboard</a>
    </nav>
</aside>
