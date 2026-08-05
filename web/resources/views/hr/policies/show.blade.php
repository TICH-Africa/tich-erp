@extends('layouts.hr')

@section('title', $policy->title)

@section('hr-content')
    <x-page-toolbar :title="$policy->title" />

    <div class="tich-grid tich-grid--2 tich-mb-8">
        <article class="tich-card">
            <h3 class="tich-h3">Policy Details</h3>
            <div class="tich-mt-4">
                <p><strong>Title:</strong> {{ $policy->title }}</p>
                <p><strong>Category:</strong> {{ ucfirst($policy->category) }}</p>
                <p><strong>Effective Date:</strong> {{ $policy->effective_date?->format('Y-m-d') ?? '—' }}</p>
                <p><strong>Expiry Date:</strong> {{ $policy->expiry_date?->format('Y-m-d') ?? '—' }}</p>
                <p><strong>Status:</strong> {{ $policy->is_active ? 'Active' : 'Inactive' }}</p>
                <p><strong>File:</strong> {{ $policy->original_filename }}</p>
                <p><strong>Uploaded By:</strong> {{ $policy->uploadedBy?->fullName() ?? '—' }}</p>
                <p><strong>Tags:</strong> {{ $policy->tags ?: '—' }}</p>
                @if ($policy->description)
                    <p class="tich-mt-4"><strong>Description:</strong></p>
                    <p class="tich-text tich-text--secondary">{{ $policy->description }}</p>
                @endif
            </div>
        </article>

        <article class="tich-card">
            <h3 class="tich-h3">Actions</h3>
            <div class="tich-mt-4">
                <a href="{{ route('hr.policies.view', $policy) }}" class="tich-btn tich-btn-primary tich-mb-4" target="_blank">Open Document</a>
                <a href="{{ route('hr.policies.edit', $policy) }}" class="tich-btn tich-btn-secondary tich-mb-4">Edit & Replace</a>
                <form method="POST" action="{{ route('hr.policies.destroy', $policy) }}" class="tich-mt-4" onsubmit="return confirm('Are you sure you want to delete this policy?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tich-btn tich-btn-ghost" style="color: #c53030; border-color: #c53030;">Delete</button>
                </form>
            </div>
        </article>
    </div>

    <article class="tich-card">
        <h3 class="tich-h3">Document Preview</h3>
        <div class="tich-mt-4" style="border: 1px solid var(--tich-neutral-border); border-radius: var(--radius-md); overflow: hidden; background: #f8fafc; min-height: 400px;">
            @if (str_contains($policy->mime_type, 'pdf'))
                <iframe src="{{ route('hr.policies.view', $policy) }}" width="100%" height="600px" style="border: none; display: block;"></iframe>
            @elseif (str_contains($policy->mime_type, 'image'))
                <img src="{{ route('hr.policies.view', $policy) }}" alt="{{ $policy->title }}" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">
            @else
                <div style="padding: 3rem; text-align: center;">
                    <p class="tich-text tich-text--secondary">Preview not available for this file type.</p>
                    <a href="{{ route('hr.policies.download', $policy) }}" class="tich-btn tich-btn-primary tich-mt-4" target="_blank">Download to view</a>
                </div>
            @endif
        </div>
    </article>
@endsection
