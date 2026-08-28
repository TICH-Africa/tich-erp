@php
    use App\Support\PublicAsset;
    use App\Support\Seo;

    $orgName = $siteMeta['institution_name'] ?? 'TICH in Africa';
    $orgUrl = Seo::absoluteUrl(rtrim((string) config('app.url'), '/') ?: url('/')) ?? url('/');
    $logoUrl = $siteMeta['search_icon_url'] ?? $siteMeta['logo_url'] ?? PublicAsset::url('images/logo-mark.png');
    $logoObject = Seo::imageObject($logoUrl, $orgName);
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
        'logo' => $logoObject,
        'image' => $logoObject,
        'description' => $siteMeta['meta_description'] ?? $siteMeta['tagline'] ?? null,
        'sameAs' => $sameAs !== [] ? $sameAs : null,
    ];

    $website = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteMeta['short_name'] ?? $orgName,
        'url' => $orgUrl,
        'publisher' => array_filter([
            '@type' => 'EducationalOrganization',
            'name' => $orgName,
            'logo' => $logoObject,
        ]),
    ];

    $organization = array_filter($organization, fn ($v) => $v !== null && $v !== []);
@endphp
<script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($website, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
