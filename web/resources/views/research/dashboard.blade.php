@extends('layouts.research')

@section('title', 'Research Dashboard')

@section('research-content')
<div class="tich-mod-dash">
    <header class="tich-mod-dash__hero">
        <div class="tich-mod-dash__hero-copy">
            <p class="tich-mod-dash__eyebrow">Knowledge &amp; discovery</p>
            <h1 class="tich-mod-dash__title">Research command center</h1>
            <p class="tich-mod-dash__lede">Research projects, grants, publications, and ethics — module overview.</p>
        </div>
    </header>

    @include('qa.partials.assigned-tasks-panel')

    <article class="tich-mod-dash__panel">
        <div class="tich-mod-dash__panel-head">
            <div>
                <p class="tich-mod-dash__panel-eyebrow">Status</p>
                <h2 class="tich-mod-dash__panel-title">Module ready</h2>
                <p class="tich-mod-dash__panel-meta">The Research module is set up and ready. Project tracking, grant management, and publication records will appear here as they are enabled.</p>
            </div>
        </div>
    </article>
</div>
@endsection
