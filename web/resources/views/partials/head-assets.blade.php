@php
    $siteMetaForHead = $siteMeta ?? app(\App\Services\SiteSettingsService::class)->siteMeta();
@endphp
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="format-detection" content="telephone=no,date=no,address=no,email=no">
<meta name="color-scheme" content="light dark">
<link rel="icon" href="{{ $siteMetaForHead['favicon_url'] }}" type="{{ $siteMetaForHead['favicon_type'] }}">
<link rel="shortcut icon" href="{{ $siteMetaForHead['favicon_url'] }}" type="{{ $siteMetaForHead['favicon_type'] }}">
<link rel="apple-touch-icon" href="{{ $siteMetaForHead['favicon_url'] }}">
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
@include('partials.theme-init')
<link href="https://fonts.bunny.net/css?family=merriweather:400,700" rel="stylesheet" type="text/css" />
<x-asset.stylesheet path="css/tich-platform.css" />
<x-asset.script path="js/tich-theme.js" />
<x-asset.script path="js/tich-select.js" />
<x-asset.script path="js/tich-toasts.js" />
<x-asset.script path="js/tich-lazy-load.js" />
<x-asset.script path="js/tich-form-submit-once.js" />
@if (config('security.block_inspect_ui', true))
    <x-asset.script path="js/tich-ui-protection.js" />
@endif
<script src="https://cdn.tailwindcss.com" type="text/javascript"></script>
<script type="text/javascript">
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
