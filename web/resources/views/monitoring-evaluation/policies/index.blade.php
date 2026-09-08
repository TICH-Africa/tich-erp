@extends('layouts.monitoring-evaluation')

@section('title', 'M&E Policy Portal')

@section('monitoring-evaluation-content')
    <x-page-toolbar title="M&E Policy Portal" meta="Upload and publish the Standard M&E Policy for HOD digital sign-off">
        <x-slot:actions>
            <a href="{{ route('monitoring_evaluation.policies.create') }}" class="tich-btn tich-btn-primary">Upload policy</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    @if ($current)
        <article class="tich-card tich-mt-6">
            <h2 class="tich-h3">Published for {{ $current->fiscal_year }}</h2>
            <p class="tich-text tich-mt-2">{{ $current->title }}@if($current->version) · v{{ $current->version }}@endif</p>
            @if ($signoff)
                <p class="tich-caption tich-mt-2">Sign-offs: {{ $signoff['signed'] }} / {{ $signoff['total'] }} departments with HODs</p>
            @endif
            <a href="{{ route('monitoring_evaluation.policies.show', $current) }}" class="tich-btn tich-btn-secondary tich-mt-4">Open</a>
        </article>
    @endif

    <div class="tich-card tich-table-panel tich-mt-6">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->title }}@if($item->version) <span class="tich-caption">v{{ $item->version }}</span>@endif</td>
                        <td>{{ $item->fiscal_year }}</td>
                        <td>{{ ucfirst($item->status) }}</td>
                        <td>{{ $item->uploaded_at?->format('d M Y') }}</td>
                        <td><a href="{{ route('monitoring_evaluation.policies.show', $item) }}" class="tich-link">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No policies uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="tich-mt-4">{{ $items->links() }}</div>
    </div>
@endsection
