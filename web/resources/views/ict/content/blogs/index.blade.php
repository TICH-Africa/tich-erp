@extends('layouts.ict')

@section('title', 'Blog posts')

@section('ict-content')
    <x-page-toolbar title="Blog posts" meta="Homepage and public blog content">
        <x-slot:actions>
            <a href="{{ route('ict.content.blogs.create') }}" class="tich-btn tich-btn-primary">Add post</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    <tr>
                        <td>
                            @if ($post->imageUrl())
                                <img src="{{ $post->imageUrl() }}" alt="" class="tich-program-admin-thumb">
                            @else
                                <span class="tich-caption">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('ict.content.blogs.edit', $post) }}" class="tich-link">{{ $post->title }}</a>
                            @if ($post->status === 'published' && $post->slug)
                                <p class="tich-caption tich-mt-1">
                                    <a href="{{ route('blog.show', $post->slug) }}" class="tich-link" target="_blank" rel="noopener">View public page</a>
                                </p>
                            @endif
                        </td>
                        <td>{{ ucfirst($post->status) }}</td>
                        <td>{{ $post->published_at?->format('d M Y') ?? '-' }}</td>
                        <td style="display:flex;gap:0.35rem;">
                            <a href="{{ route('ict.content.blogs.edit', $post) }}" class="tich-squircle-btn" title="Edit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('ict.content.blogs.destroy', $post) }}" onsubmit="return confirm('Delete this post?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete">×</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No blog posts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
