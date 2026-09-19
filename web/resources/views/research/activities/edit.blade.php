@extends('layouts.research')

@section('title', 'Edit research activity')

@section('research-content')
    <x-page-toolbar title="Edit research activity" meta="{{ $activity->title }}">
        <x-slot:actions>
            @if ($activity->isPublished() && $activity->slug)
                <a href="{{ route('research.show', $activity->slug) }}" class="tich-btn tich-btn-secondary" target="_blank" rel="noopener">View public page</a>
            @endif
            <a href="{{ route('research.activities.show', $activity) }}" class="tich-btn tich-btn-secondary">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="uf-form">
        <form method="POST" action="{{ route('research.activities.update', $activity) }}" enctype="multipart/form-data" data-uf="ready" data-research-activity-form>
            @csrf
            @method('PUT')
            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">Research</div>
                    <div class="uf-amount-bar__sum">{{ $activity->title }}</div>
                </div>
                <span class="uf-badge">{{ ucfirst($activity->visibility) }}</span>
            </div>

            @include('research.activities._form', [
                'activity' => $activity,
                'durationUnits' => $durationUnits,
                'lifecycleStatuses' => $lifecycleStatuses,
                'visibilities' => $visibilities,
                'uploadUrl' => $uploadUrl,
            ])

            <div class="uf-form-section">
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <a href="{{ route('research.activities.show', $activity) }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        <button type="submit" class="uf-btn uf-btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    @parent
    <x-asset.script path="js/tich-cms-editor.js" :defer="false" />
    <x-asset.script path="js/tich-research-activity-form.js" :defer="false" />
@endsection
