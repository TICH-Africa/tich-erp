@php
    $siteMetaForHead = $siteMeta ?? app(\App\Services\SiteSettingsService::class)->siteMeta();
@endphp
<link rel="icon" href="{{ $siteMetaForHead['favicon_url'] }}" type="{{ $siteMetaForHead['favicon_type'] }}">
<link rel="shortcut icon" href="{{ $siteMetaForHead['favicon_url'] }}" type="{{ $siteMetaForHead['favicon_type'] }}">
<link rel="apple-touch-icon" href="{{ $siteMetaForHead['favicon_url'] }}">
<link rel="preconnect" href="https://fonts.bunny.net">
@include('partials.theme-init')
<link href="https://fonts.bunny.net/css?family=merriweather:400,700" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('css/tich-platform.css') }}">
<script src="{{ asset('js/tich-theme.js') }}" defer></script>
<script src="{{ asset('js/tich-select.js') }}" defer></script>
<script src="{{ asset('js/tich-toasts.js') }}" defer></script>
<script src="{{ asset('js/tich-lazy-load.js') }}" defer></script>
<script src="{{ asset('js/tich-form-submit-once.js') }}" defer></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    tich: {
                        green: '#6CAB33',
                        'green-dark': '#5A9430',
                        'green-light': '#E8F3DC',
                        blue: '#1669A6',
                        'blue-dark': '#125A8C',
                        'blue-light': '#D6E8F5',
                        grey: '#494C50',
                        neutral: '#F5F6F6',
                        'neutral-border': '#E2E4E5',
                        'neutral-muted': '#6B6E72',
                    },
                },
                fontFamily: {
                    heading: ['"Times New Roman"', 'Times', 'serif'],
                    body: ['Merriweather', 'Georgia', 'serif'],
                    ui: ['Arial', 'Calibri', 'ui-sans-serif', 'sans-serif'],
                },
            },
        },
    };
</script>
