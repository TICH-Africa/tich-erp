@extends('layouts.site-settings')

@section('site-settings-content')
    <x-page-toolbar title="Site settings" :meta="match($panel) {
        'general' => 'Site identity, navbar branding, and logo',
        'hero' => 'Homepage hero carousel slides',
        'contact' => 'Phone numbers, emails, and locations',
        'social' => 'Social media profile links',
        default => 'Public site configuration',
    }" />

    @if ($panel === 'general')
        @include('site-settings.partials.panel-general')
    @elseif ($panel === 'hero')
        @include('site-settings.partials.panel-hero')
    @elseif ($panel === 'contact')
        @include('site-settings.partials.panel-contact')
    @else
        @include('site-settings.partials.panel-social')
    @endif
@endsection
