@extends('layouts.ict')

@section('title', 'Legal pages')

@section('ict-content')
    <x-page-toolbar title="Legal pages" meta="Privacy Policy and Terms and Conditions shown on the public site" />

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td>
                            <a href="{{ route('ict.content.pages.edit', $page) }}" class="tich-link">{{ $page->title }}</a>
                            @if ($page->isPublished())
                                <p class="tich-caption tich-mt-1">
                                    <a href="{{ $page->slug === 'privacy' ? route('privacy') : route('terms') }}" class="tich-link" target="_blank" rel="noopener">View public page</a>
                                </p>
                            @endif
                        </td>
                        <td><code>{{ $page->slug }}</code></td>
                        <td>{{ ucfirst($page->status) }}</td>
                        <td>{{ $page->updated_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <a href="{{ route('ict.content.pages.edit', $page) }}" class="tich-squircle-btn" title="Edit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No legal pages found. Run migrations to seed Privacy Policy and Terms.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
