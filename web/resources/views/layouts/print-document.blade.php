<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle ?? 'Official document' }} - {{ $institution['short_name'] ?? 'TICH' }}</title>
    @if (empty($forPdf))
        <x-asset.stylesheet path="css/tich-print-documents.css" />
        @if (config('security.block_inspect_ui', true))
            <x-asset.script path="js/tich-ui-protection.js" />
        @endif
    @else
        <style>@include('partials.print.document-styles-inline')</style>
    @endif
</head>
<body @class(array_filter([
    'tich-doc--landscape' => ($paperOrientation ?? 'portrait') === 'landscape',
    $bodyClass ?? null,
]))>
    @if (empty($forPdf) && empty($hideActions))
        @include('partials.print.document-actions')
    @endif

    <article class="tich-doc-sheet">
        @include('partials.print.document-letterhead')

        <div class="tich-doc-title-block">
            <h1>{{ $documentTitle ?? 'Official document' }}</h1>
            @if (! empty($documentSubtitle))
                <p>{{ $documentSubtitle }}</p>
            @endif
            @if (! empty($documentRef))
                <p>Document ref: {{ $documentRef }} · Generated {{ ($generatedAt ?? now())->format('d F Y') }}</p>
            @endif
        </div>

        @if (! empty($metaRows))
            @include('partials.print.document-meta', ['rows' => $metaRows])
        @endif

        @yield('document-content')

        @include('partials.print.document-footer')
    </article>
</body>
</html>
