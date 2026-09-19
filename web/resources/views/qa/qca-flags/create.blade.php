@extends('layouts.qa')

@section('title', 'Raise QCA Flag')

@section('qa-content')
    <x-page-toolbar title="Raise QCA Flag" meta="Quality Corrective Action flag for compliance failures" />

    <div class="uf-form">
        <form method="POST" action="{{ route('qa.qca-flags.store') }}" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">QCA · Compliance flag</div>
                    <div class="uf-amount-bar__sum">Raise QCA Flag</div>
                </div>
                <span class="uf-badge">New</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Classification</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-3">
                        <div class="uf-field">
                            <label for="category">Category <span class="uf-req">*</span></label>
                            <select id="category" name="category" required class="{{ $errors->has('category') ? 'is-invalid' : '' }}">
                                <option value="">Select category</option>
                                @foreach (\App\Models\Qa\QcaFlag::CATEGORIES as $cat)
                                    <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('category')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field">
                            <label for="severity">Severity <span class="uf-req">*</span></label>
                            <select id="severity" name="severity" required class="{{ $errors->has('severity') ? 'is-invalid' : '' }}">
                                <option value="">Select severity</option>
                                @foreach (\App\Models\Qa\QcaFlag::SEVERITIES as $sev)
                                    <option value="{{ $sev }}" @selected(old('severity') === $sev)>{{ $sev }}</option>
                                @endforeach
                            </select>
                            @error('severity')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field">
                            <label for="assigned_to">Assign to</label>
                            <select id="assigned_to" name="assigned_to" class="{{ $errors->has('assigned_to') ? 'is-invalid' : '' }}">
                                <option value="">Unassigned</option>
                                @foreach ($staff as $s)
                                    <option value="{{ $s->id }}" @selected(old('assigned_to') == $s->id)>{{ $s->first_name }} {{ $s->surname }} ({{ $s->job_title }})</option>
                                @endforeach
                            </select>
                            @error('assigned_to')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Description</div>
                <div class="uf-section-body">
                    <div class="uf-field">
                        <label for="description">Description <span class="uf-req">*</span></label>
                        <textarea id="description" name="description" rows="4" required placeholder="Describe the compliance failure in detail" class="{{ $errors->has('description') ? 'is-invalid' : '' }}">{{ old('description') }}</textarea>
                        @error('description')<span class="uf-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Target &amp; source</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-3">
                        <div class="uf-field">
                            <label for="target_entity_type">Target entity type</label>
                            <input id="target_entity_type" name="target_entity_type" maxlength="100" placeholder="e.g. department, programme, hub" value="{{ old('target_entity_type') }}" class="{{ $errors->has('target_entity_type') ? 'is-invalid' : '' }}">
                            @error('target_entity_type')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field">
                            <label for="target_entity_id">Target entity ID</label>
                            <input id="target_entity_id" name="target_entity_id" type="number" min="0" value="{{ old('target_entity_id') }}" class="{{ $errors->has('target_entity_id') ? 'is-invalid' : '' }}">
                            @error('target_entity_id')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="uf-field">
                            <label for="resolution_deadline">Resolution deadline</label>
                            <input id="resolution_deadline" type="date" name="resolution_deadline" value="{{ old('resolution_deadline') }}" class="{{ $errors->has('resolution_deadline') ? 'is-invalid' : '' }}">
                            @error('resolution_deadline')<span class="uf-error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="source_module">Source module</label>
                            <input id="source_module" name="source_module" maxlength="100" placeholder="e.g. Module 1, Module 2" value="{{ old('source_module') }}">
                        </div>
                        <div class="uf-field">
                            <label for="source_entity_id">Source entity ID</label>
                            <input id="source_entity_id" name="source_entity_id" type="number" min="0" value="{{ old('source_entity_id') }}">
                        </div>
                    </div>
                    <p class="uf-hint">High/Critical severity flags will automatically lock downstream modules per the QCA lock matrix.</p>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Raise flag</button>
                        <a href="{{ route('qa.qca-flags.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>
@endsection
