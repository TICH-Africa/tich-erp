@extends('layouts.ict')

@section('title', 'ICT Dashboard')

@section('ict-content')
    <x-page-toolbar title="Information & Communication Technology" meta="Systems, infrastructure, support, and digital services" />

    <article class="tich-card tich-mt-8">
        <p class="tich-text">Manage ERP access, infrastructure, and support from this module. Use registration invites to onboard staff who do not yet have portal accounts.</p>
        <p class="tich-caption tich-mt-2"><a href="{{ route('ict.registration-invites.index') }}">Open ERP registration invites</a></p>
    </article>
@endsection
