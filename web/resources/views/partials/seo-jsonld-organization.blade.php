@php
    $orgName = $siteMeta['institution_name'] ?? 'TICH in Africa';
    $orgUrl = rtrim((string) config('app.url'), '/') ?: url('/');
    $logo = $siteMeta['logo_url'] ?? \App\Support\PublicAsset::url('images/logo.png');
    $sameAs = collect($socialLinks ?? [])
        ->pluck('url')
        ->filter()
        ->values()
        ->all();

    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => $orgName,
        'alternateName' => $siteMeta['short_name'] ?? 'TICH',
        'url' => $orgUrl,
        'logo' => $logo,
        'description' => $siteMeta['meta_description'] ?? $siteMeta['tagline'] ?? null,
        'sameAs' => $sameAs !== [] ? $sameAs : null,
    ];

    $website = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteMeta['short_name'] ?? $orgName,
        'url' => $orgUrl,
        'publisher' => [
            '@type' => 'EducationalOrganization',
            'name' => $orgName,
        ],
    ];

    $organization = array_filter($organization, fn ($v) => $v !== null && $v !== []);
@endphp
<script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($website, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
