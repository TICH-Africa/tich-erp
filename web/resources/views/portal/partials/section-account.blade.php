<header class="tich-dept-header">
    <p class="tich-caption">Account</p>
    <h1 class="tich-h1 tich-dept-header__title">Security &amp; account</h1>
</header>

<div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
    <article class="tich-card">
        <h2 class="tich-h3">Portal account</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Username</dt>
            <dd>{{ $biodata['portal']['username'] ?? auth()->user()->username }}</dd>
            <dt class="tich-caption">Email</dt>
            <dd>{{ $biodata['contact']['email'] ?? auth()->user()->email }}</dd>
            <dt class="tich-caption">Last login</dt>
            <dd>{{ $biodata['portal']['last_login_at'] ?? '-' }}</dd>
            <dt class="tich-caption">Portal activated</dt>
            <dd>{{ $biodata['enrollment']['portal_activated_at'] ?? '-' }}</dd>
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Security</h2>
        <p class="tich-text tich-mt-2">
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
        <h2 class="tich-h3">Sign out</h2>
        <p class="tich-text tich-mt-2">Sign out securely from this device.</p>
        <form method="POST" action="{{ route('logout') }}" class="tich-mt-4">
            @csrf
            <button type="submit" class="tich-btn tich-btn-secondary">Sign out</button>
        </form>
    </article>
</div>
