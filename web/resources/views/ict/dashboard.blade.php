@extends('layouts.ict')

@section('title', 'ICT Dashboard')

@section('ict-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Systems &amp; digital services</p>
            <h1 class="tich-mod-dash__title">ICT command center</h1>
            <p class="tich-mod-dash__lede">ERP access, infrastructure, website content, and platform health — live overview.</p>
        </div>
    </header>

    <article class="tich-mod-dash__panel">
        <div class="tich-mod-dash__panel-head">
            <div>
                <p class="tich-mod-dash__panel-eyebrow">Operations</p>
                <h2 class="tich-mod-dash__panel-title">Module focus</h2>
                <p class="tich-mod-dash__panel-meta">Manage ERP access, infrastructure, and support from this module. Use registration invites to onboard staff who do not yet have portal accounts.</p>
            </div>
        </div>
    </article>

    <section class="tich-mod-dash__nav" aria-label="ICT shortcuts">
        <p class="tich-mod-dash__section-label">Quick routes</p>
        <div class="tich-mod-dash__nav-grid">
            <a href="{{ route('ict.platform-performance.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">01</span>
                <h3 class="tich-mod-dash__nav-title">Platform performance</h3>
                <p class="tich-mod-dash__nav-text">Live server, database, and host resource metrics.</p>
            </a>
            <a href="{{ route('ict.registration-invites.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">02</span>
                <h3 class="tich-mod-dash__nav-title">Registration invites</h3>
                <p class="tich-mod-dash__nav-text">Invite staff to create ERP portal accounts.</p>
            </a>
            @can('users.access.manage')
                <a href="{{ route('ict.users.index') }}" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">03</span>
                    <h3 class="tich-mod-dash__nav-title">Users &amp; access</h3>
                    <p class="tich-mod-dash__nav-text">Manage user accounts and module access.</p>
                </a>
                <a href="{{ route('ict.roles.index') }}" class="tich-mod-dash__nav-card">
                    <span class="tich-mod-dash__nav-index">04</span>
                    <h3 class="tich-mod-dash__nav-title">User roles</h3>
                    <p class="tich-mod-dash__nav-text">Configure roles and permission categories.</p>
                </a>
            @endcan
            <a href="{{ route('ict.content.blogs.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">05</span>
                <h3 class="tich-mod-dash__nav-title">Website content</h3>
                <p class="tich-mod-dash__nav-text">Blogs, events, courses, and legal pages.</p>
            </a>
            <a href="{{ route('ict.content.about.index') }}" class="tich-mod-dash__nav-card">
                <span class="tich-mod-dash__nav-index">06</span>
                <h3 class="tich-mod-dash__nav-title">About Us</h3>
                <p class="tich-mod-dash__nav-text">Edit public About Us content blocks.</p>
            </a>
        </div>
    </section>
</div>
@endsection
