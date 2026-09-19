@extends('layouts.research')

@section('title', 'Post research activity')

@section('research-content')
    <x-page-toolbar title="Post research activity" meta="Schedule, describe, and attach documents">
        <x-slot:actions>
            <a href="{{ route('research.activities.index') }}" class="tich-btn tich-btn-secondary">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="uf-form">
        <form method="POST" action="{{ route('research.activities.store') }}" enctype="multipart/form-data" data-uf="ready" data-research-activity-form>
            @csrf
            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">Research</div>
                    <div class="uf-amount-bar__sum">New activity</div>
                </div>
                <span class="uf-badge">Compose</span>
            </div>

            @include('research.activities._form', [
                'durationUnits' => $durationUnits,
                'lifecycleStatuses' => $lifecycleStatuses,
                'visibilities' => $visibilities,
                'uploadUrl' => $uploadUrl,
            ])

            <div class="uf-form-section">
                <div class="uf-section-body">
                    <div class="uf-form-actions">
                        <a href="{{ route('research.activities.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                        <button type="submit" class="uf-btn uf-btn-primary">Save activity</button>
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
