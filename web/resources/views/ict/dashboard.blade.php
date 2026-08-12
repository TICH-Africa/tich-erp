@extends('layouts.ict')

@section('title', 'ICT Dashboard')

@section('ict-content')
    <x-page-toolbar title="Information & Communication Technology" meta="Systems, infrastructure, support, and digital services" />

    <article class="tich-card tich-mt-8">
        <p class="tich-text">Manage ERP access, infrastructure, and support from this module. Use registration invites to onboard staff who do not yet have portal accounts.</p>
        <ul class="tich-text tich-mt-4">
            <li><a href="{{ route('ict.registration-invites.index') }}">ERP registration invites</a></li>
            @can('users.access.manage')
                <li><a href="{{ route('ict.users.index') }}">Users &amp; access</a></li>
                <li><a href="{{ route('ict.roles.index') }}">User roles</a></li>
            @endcan
        </ul>
    </article>
@endsection
