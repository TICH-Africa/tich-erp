<header class="tich-dept-header">
    <p class="tich-caption">My services</p>
    <h1 class="tich-h1 tich-dept-header__title">My profile</h1>
    <p class="tich-text tich-dept-header__meta">{{ $student->registration_number }} · {{ $biodata['academic']['program'] }}</p>
</header>

<div class="tich-grid tich-grid--2 tich-mt-8" style="align-items: start; gap: 1.5rem;">
    <article class="tich-card">
        <h2 class="tich-h3">Identity</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            @foreach ($biodata['identity'] as $label => $value)
                <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                <dd>{{ $value ?: '—' }}</dd>
            @endforeach
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Contact</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            @foreach ($biodata['contact'] as $label => $value)
                <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                <dd>{{ $value ?: '—' }}</dd>
            @endforeach
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Academic profile</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            @foreach ($biodata['academic'] as $label => $value)
                <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                <dd>{{ $value ?: '—' }}</dd>
            @endforeach
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Next of kin</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            @foreach ($biodata['next_of_kin'] as $label => $value)
                <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                <dd>{{ $value ?: '—' }}</dd>
            @endforeach
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Emergency contact</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            @foreach ($biodata['emergency'] as $label => $value)
                <dt class="tich-caption">{{ ucwords(str_replace('_', ' ', $label)) }}</dt>
                <dd>{{ $value ?: '—' }}</dd>
            @endforeach
        </dl>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Portal access</h2>
        <dl style="display: grid; grid-template-columns: 9rem 1fr; gap: 0.5rem 1rem; margin: 1rem 0 0;">
            <dt class="tich-caption">Username</dt>
            <dd>{{ $biodata['portal']['username'] ?? auth()->user()->username }}</dd>
            <dt class="tich-caption">Activated</dt>
            <dd>{{ $biodata['enrollment']['portal_activated_at'] ?? '—' }}</dd>
            <dt class="tich-caption">Last login</dt>
            <dd>{{ $biodata['portal']['last_login_at'] ?? '—' }}</dd>
        </dl>
    </article>
</div>
