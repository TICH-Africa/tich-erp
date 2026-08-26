@extends('layouts.ict')

@section('title', 'Blog posts')

@section('ict-content')
    @php
        $openCreate = $errors->any() && old('_method') !== 'PUT';
        $editId = old('_method') === 'PUT' ? (int) old('edit_post_id') : null;
        $editPost = $editId ? $posts->firstWhere('id', $editId) : null;
    @endphp

    <x-page-toolbar title="Blog posts" meta="Homepage and public blog content">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="blog-create-modal">Add post</button>
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
                                <span class="tich-caption">—</span>
                            @endif
                        </td>
                        <td>{{ $post->title }}</td>
                        <td>{{ ucfirst($post->status) }}</td>
                        <td>{{ $post->published_at?->format('d M Y') ?? '—' }}</td>
                        <td style="display:flex;gap:0.35rem;">
                            <button
                                type="button"
                                class="tich-squircle-btn blog-edit-trigger"
                                data-open-modal="blog-edit-modal"
                                data-update-url="{{ route('ict.content.blogs.update', $post) }}"
                                data-id="{{ $post->id }}"
                                data-title="{{ $post->title }}"
                                data-subtitle="{{ $post->subtitle }}"
                                data-excerpt="{{ $post->excerpt }}"
                                data-body="{{ $post->body }}"
                                data-status="{{ $post->status }}"
                                data-image="{{ $post->imageUrl() ?? '' }}"
                                title="Edit"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                            </button>
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

    <div id="blog-create-modal" class="tich-modal{{ $openCreate ? ' is-open' : '' }}" aria-hidden="{{ $openCreate ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="blog-create-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header">
                <h2 class="tich-h3">Add blog post</h2>
                <button type="button" class="tich-squircle-btn" data-close-modal="blog-create-modal">×</button>
            </div>
            <form method="POST" action="{{ route('ict.content.blogs.store') }}" enctype="multipart/form-data" class="tich-modal__body">
                @csrf
                @include('ict.content.blogs._form', ['statuses' => $statuses, 'prefix' => 'create_'])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="blog-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="blog-edit-modal" class="tich-modal{{ $editPost ? ' is-open' : '' }}" aria-hidden="{{ $editPost ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="blog-edit-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header">
                <h2 class="tich-h3">Edit blog post</h2>
                <button type="button" class="tich-squircle-btn" data-close-modal="blog-edit-modal">×</button>
            </div>
            <form id="blog-edit-form" method="POST" action="{{ $editPost ? route('ict.content.blogs.update', $editPost) : '#' }}" enctype="multipart/form-data" class="tich-modal__body">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_post_id" id="edit_post_id" value="{{ $editPost?->id }}">
                @include('ict.content.blogs._form', ['post' => $editPost, 'statuses' => $statuses, 'prefix' => 'edit_'])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="blog-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    @include('admin.partials.tich-modal-assets')
    <script>
        document.querySelectorAll('.blog-edit-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = document.getElementById('blog-edit-form');
                form.action = btn.getAttribute('data-update-url');
                document.getElementById('edit_post_id').value = btn.getAttribute('data-id') || '';
                ['title','subtitle','excerpt','body','status'].forEach(function (k) {
                    var el = document.getElementById('edit_' + k);
                    if (el) el.value = btn.getAttribute('data-' + k) || '';
                });
            });
        });
    </script>
    @endpush
@endsection
