@extends('layouts.qa')

@section('title', 'Raise QCA Flag')

@section('qa-content')
    <x-page-toolbar title="Raise QCA Flag" meta="Quality Corrective Action flag for compliance failures" />

    <div class="tich-card tich-mt-8">
        <form method="POST" action="{{ route('qa.qca-flags.store') }}" class="tich-form-stack">
            @csrf

            <div class="tich-grid tich-grid--3">
                <div class="tich-form-group">
                    <label class="tich-label" for="category">Category *</label>
                    <select id="category" name="category" class="tich-input" required>
                        <option value="">Select category</option>
                        @foreach (\App\Models\Qa\QcaFlag::CATEGORIES as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="severity">Severity *</label>
                    <select id="severity" name="severity" class="tich-input" required>
                        <option value="">Select severity</option>
                        @foreach (\App\Models\Qa\QcaFlag::SEVERITIES as $sev)
                            <option value="{{ $sev }}">{{ $sev }}</option>
                        @endforeach
                    </select>
                    @error('severity')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="assigned_to">Assign to</label>
                    <select id="assigned_to" name="assigned_to" class="tich-input">
                        <option value="">Unassigned</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->surname }} ({{ $s->job_title }})</option>
                        @endforeach
                    </select>
                    @error('assigned_to')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="description">Description *</label>
                <textarea id="description" name="description" class="tich-input" rows="4" required placeholder="Describe the compliance failure in detail">{{ old('description') }}</textarea>
                @error('description')<p class="tich-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="tich-grid tich-grid--3">
                <div class="tich-form-group">
                    <label class="tich-label" for="target_entity_type">Target entity type</label>
                    <input id="target_entity_type" name="target_entity_type" class="tich-input" maxlength="100" placeholder="e.g. department, programme, hub" value="{{ old('target_entity_type') }}">
                    @error('target_entity_type')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="target_entity_id">Target entity ID</label>
                    <input id="target_entity_id" name="target_entity_id" class="tich-input" type="number" min="0" value="{{ old('target_entity_id') }}">
                    @error('target_entity_id')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="resolution_deadline">Resolution deadline</label>
                    <input id="resolution_deadline" type="date" name="resolution_deadline" class="tich-input" value="{{ old('resolution_deadline') }}">
                    @error('resolution_deadline')<p class="tich-field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="tich-grid tich-grid--3">
                <div class="tich-form-group">
                    <label class="tich-label" for="source_module">Source module</label>
                    <input id="source_module" name="source_module" class="tich-input" maxlength="100" placeholder="e.g. Module 1, Module 2" value="{{ old('source_module') }}">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="source_entity_id">Source entity ID</label>
                    <input id="source_entity_id" name="source_entity_id" class="tich-input" type="number" min="0" value="{{ old('source_entity_id') }}">
                </div>
            </div>

            <div class="tich-form-group">
                <p class="tich-caption">⚠ High/Critical severity flags will automatically lock downstream modules per the QCA lock matrix.</p>
            </div>

            <div class="tich-form-footer">
                <button type="submit" class="tich-btn tich-btn-primary">Raise flag</button>
                <a href="{{ route('qa.qca-flags.index') }}" class="tich-btn tich-btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
