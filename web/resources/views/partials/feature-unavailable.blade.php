@extends($layout)

@section($contentSection)
    <x-page-toolbar :title="$featureLabel" meta="Inactive" />

    <article class="tich-card tich-mt-6">
        <span class="tich-badge tich-badge--secondary tich-mb-4" style="display:inline-block;">Inactive</span>
        @include('partials.states.empty', [
            'title' => 'This page is unavailable',
            'description' => 'This section is currently turned off. Contact HR if you need assistance.',
            'icon' => 'wifi-off',
        ])
    </article>
@endsection
