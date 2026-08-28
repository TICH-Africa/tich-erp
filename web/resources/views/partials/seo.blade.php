@php
    use App\Support\Seo;

    $incomingSeo = is_array($seo ?? null) ? $seo : [];
    $pageKey = $seoPageKey ?? null;
    $pageDefaults = $pageKey ? Seo::pageDefaults($pageKey) : [];

    $sectionTitle = trim($__env->yieldContent('title'));
    $sectionDescription = trim($__env->yieldContent('meta_description'));
    $sectionRobots = trim($__env->yieldContent('meta_robots'));

    $seo = Seo::build([
        'title' => $sectionTitle !== '' ? $sectionTitle : ($incomingSeo['title'] ?? $pageDefaults['title'] ?? 'Home'),
        'description' => $sectionDescription !== ''
            ? $sectionDescription
            : ($incomingSeo['description'] ?? $pageDefaults['description'] ?? null),
        'image' => $incomingSeo['image'] ?? null,
        'url' => $incomingSeo['url'] ?? null,
        'type' => $incomingSeo['type'] ?? null,
        'robots' => $sectionRobots !== '' ? $sectionRobots : ($incomingSeo['robots'] ?? null),
        'published_time' => $incomingSeo['published_time'] ?? null,
        'modified_time' => $incomingSeo['modified_time'] ?? null,
    ], $siteMeta ?? []);
@endphp

<title>{{ $seo['full_title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<meta name="author" content="{{ $seo['site_name'] }}">
<link rel="canonical" href="{{ $seo['url'] }}">

<meta property="og:locale" content="{{ $seo['locale'] }}">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $seo['full_title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['url'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
@if (! empty($seo['image_width']) && ! empty($seo['image_height']))
    <meta property="og:image:width" content="{{ $seo['image_width'] }}">
    <meta property="og:image:height" content="{{ $seo['image_height'] }}">
@endif
@if (! empty($seo['image_alt']))
    <meta property="og:image:alt" content="{{ $seo['image_alt'] }}">
@endif
@if (! empty($seo['published_time']))
    <meta property="article:published_time" content="{{ $seo['published_time'] }}">
@endif
@if (! empty($seo['modified_time']))
    <meta property="article:modified_time" content="{{ $seo['modified_time'] }}">
@endif

<meta name="twitter:card" content="{{ $seo['twitter_card'] }}">
<meta name="twitter:title" content="{{ $seo['full_title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">

@hasSection('seo_jsonld')
    @yield('seo_jsonld')
@else
    @include('partials.seo-jsonld-organization')
@endif
