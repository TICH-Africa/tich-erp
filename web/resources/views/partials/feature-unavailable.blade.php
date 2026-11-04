@extends($layout)

@section($contentSection)
    <x-page-toolbar :title="$featureLabel" />

    <article class="tich-card tich-mt-6">
        @include('partials.states.empty', [
            'title' => 'This page is unavailable',
            'icon' => 'wifi-off',
        ])
    </article>
@endsection
