@extends('layouts.ict')

@section('title', 'About Us content')

@section('ict-content')
    @php
        $openCreate = $errors->any() && old('_method') !== 'PUT';
        $editId = old('_method') === 'PUT' ? (int) old('edit_block_id') : null;
        $editBlock = $editId ? $blocks->firstWhere('id', $editId) : null;
    @endphp

    @include('site-settings.partials.sortable-styles')

    <x-page-toolbar title="About Us" meta="Public About Us sections — drag to reorder, edit content and optional image">
        <x-slot:actions>
            <a href="{{ route('about') }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View public page</a>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="about-create-modal">Add section</button>
        </x-slot:actions>
    </x-page-toolbar>

    <p id="about-sort-status" class="tich-caption tich-mb-4" aria-live="polite"></p>

    <div class="tich-card tich-table-panel">
        <table class="tich-admin-table tich-site-settings-sortable">
            <thead>
                <tr>
                    <th style="width: 2.5rem;"></th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th style="width: 6rem;"></th>
                </tr>
            </thead>
            <tbody
                data-row-sortable
                data-sort-url="{{ route('ict.content.about.reorder') }}"
                data-sort-status="#about-sort-status"
            >
                @forelse ($blocks as $block)
                    <tr data-sortable-row data-sort-id="{{ $block->id }}">
                        <td class="tich-drag-handle" title="Drag to reorder">⋮⋮</td>
                        <td>
                            @if ($block->imageUrl())
                                <img src="{{ $block->imageUrl() }}" alt="" class="tich-program-admin-thumb">
                            @else
                                <span class="tich-caption">—</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $block->title }}</div>
                            @if ($block->subtitle)
                                <p class="tich-caption tich-mt-1">{{ $block->subtitle }}</p>
                            @endif
                        </td>
                        <td>{{ $block->is_active ? 'Active' : 'Hidden' }}</td>
                        <td style="display:flex;gap:0.35rem;">
                            <button
                                type="button"
                                class="tich-squircle-btn about-edit-trigger"
                                data-open-modal="about-edit-modal"
                                data-update-url="{{ route('ict.content.about.update', $block) }}"
                                data-id="{{ $block->id }}"
                                data-title="{{ $block->title }}"
                                data-subtitle="{{ $block->subtitle }}"
                                data-body="{{ $block->body }}"
                                data-active="{{ $block->is_active ? '1' : '0' }}"
                                data-image="{{ $block->imageUrl() ?? '' }}"
                                title="Edit"
                            >
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('ict.content.about.destroy', $block) }}" onsubmit="return confirm('Delete this About section?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tich-squircle-btn" title="Delete">×</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No About sections yet. Use Add section to create one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="about-create-modal" class="tich-modal{{ $openCreate ? ' is-open' : '' }}" aria-hidden="{{ $openCreate ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="about-create-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header">
                <h2 class="tich-h3">Add About section</h2>
                <button type="button" class="tich-squircle-btn" data-close-modal="about-create-modal">×</button>
            </div>
            <form method="POST" action="{{ route('ict.content.about.store') }}" enctype="multipart/form-data" class="tich-modal__body">
                @csrf
                @include('ict.content.about._form', ['prefix' => 'create_'])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="about-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="about-edit-modal" class="tich-modal{{ $editBlock ? ' is-open' : '' }}" aria-hidden="{{ $editBlock ? 'false' : 'true' }}">
        <div class="tich-modal__backdrop" data-close-modal="about-edit-modal"></div>
        <div class="tich-modal__dialog">
            <div class="tich-modal__header">
                <h2 class="tich-h3">Edit About section</h2>
                <button type="button" class="tich-squircle-btn" data-close-modal="about-edit-modal">×</button>
            </div>
            <form id="about-edit-form" method="POST" action="{{ $editBlock ? route('ict.content.about.update', $editBlock) : '#' }}" enctype="multipart/form-data" class="tich-modal__body">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_block_id" id="edit_block_id" value="{{ $editBlock?->id }}">
                @include('ict.content.about._form', ['block' => $editBlock, 'prefix' => 'edit_'])
                <div class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="about-edit-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @include('admin.partials.tich-modal-assets')
        <script src="{{ asset('js/tich-row-sort.js') }}" defer></script>
        <script>
            document.querySelectorAll('.about-edit-trigger').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var form = document.getElementById('about-edit-form');
                    form.action = btn.getAttribute('data-update-url');
                    document.getElementById('edit_block_id').value = btn.getAttribute('data-id') || '';
                    document.getElementById('edit_title').value = btn.getAttribute('data-title') || '';
                    document.getElementById('edit_subtitle').value = btn.getAttribute('data-subtitle') || '';
                    document.getElementById('edit_body').value = btn.getAttribute('data-body') || '';
                    document.getElementById('edit_is_active').checked = btn.getAttribute('data-active') === '1';

                    var imageUrl = btn.getAttribute('data-image') || '';
                    var wrap = document.getElementById('edit_image_preview_wrap');
                    var preview = document.getElementById('edit_image_preview');
                    var removeLabel = document.getElementById('edit_remove_image_label');
                    var removeInput = document.getElementById('edit_remove_image');
                    var fileInput = document.getElementById('edit_image');

                    if (fileInput) fileInput.value = '';
                    if (removeInput) removeInput.checked = false;

                    if (imageUrl) {
                        if (preview) preview.src = imageUrl;
                        if (wrap) wrap.hidden = false;
                        if (removeLabel) removeLabel.hidden = false;
                    } else {
                        if (preview) preview.src = '';
                        if (wrap) wrap.hidden = true;
                        if (removeLabel) removeLabel.hidden = true;
                    }
                });
            });
        </script>
    @endpush
@endsection
