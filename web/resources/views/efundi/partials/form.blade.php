@php
    $opportunity = $opportunity ?? new \App\Models\EfundiOpportunity();
    $isEdit = $opportunity->exists;
    $action = $isEdit
        ? route($routePrefix.'.update', $opportunity)
        : route($routePrefix.'.store');
@endphp

<form method="post" action="{{ $action }}" class="tich-mt-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="tich-grid tich-grid--2" style="gap: 1rem;">
        <div>
            <label class="tich-label" for="efundi-title">Title <span class="uf-req">*</span></label>
            <input id="efundi-title" name="title" class="tich-input" required maxlength="255" value="{{ old('title', $opportunity->title) }}">
        </div>
        <div>
            <label class="tich-label" for="efundi-type">Type <span class="uf-req">*</span></label>
            <select id="efundi-type" name="opportunity_type" class="tich-input" required>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(old('opportunity_type', $opportunity->opportunity_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="tich-label" for="efundi-org">Organization</label>
            <input id="efundi-org" name="organization_name" class="tich-input" maxlength="255" value="{{ old('organization_name', $opportunity->organization_name) }}">
        </div>
        <div>
            <label class="tich-label" for="efundi-location">Location</label>
            <input id="efundi-location" name="location" class="tich-input" maxlength="255" value="{{ old('location', $opportunity->location) }}">
        </div>
        <div>
            <label class="tich-label" for="efundi-deadline">Application deadline</label>
            <input id="efundi-deadline" type="date" name="application_deadline" class="tich-input" value="{{ old('application_deadline', optional($opportunity->application_deadline)->format('Y-m-d')) }}">
        </div>
        <div>
            <label class="tich-label" for="efundi-url">Application link</label>
            <input id="efundi-url" type="url" name="application_url" class="tich-input" placeholder="https://..." value="{{ old('application_url', $opportunity->application_url) }}">
        </div>
        <div>
            <label class="tich-label" for="efundi-email">Application email</label>
            <input id="efundi-email" type="email" name="application_email" class="tich-input" value="{{ old('application_email', $opportunity->application_email) }}">
        </div>
    </div>

    <div class="tich-mt-4">
        <label class="tich-label" for="efundi-summary">Short summary</label>
        <textarea id="efundi-summary" name="summary" class="tich-input" rows="2" maxlength="1000">{{ old('summary', $opportunity->summary) }}</textarea>
    </div>

    <div class="tich-mt-4">
        <label class="tich-label" for="efundi-description">Full description</label>
        <textarea id="efundi-description" name="description" class="tich-input" rows="8">{{ old('description', $opportunity->description) }}</textarea>
        <p class="tich-caption tich-mt-1">Plain text or simple HTML is shown on the public e-Fundi page.</p>
    </div>

    <div class="tich-mt-4">
        <label class="tich-check">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $opportunity->is_published))>
            Publish on <a href="{{ $publicUrl }}" target="_blank" rel="noopener">e-Fundi</a> and notify students
        </label>
    </div>

    @if ($isEdit && $opportunity->is_published)
        <div class="tich-mt-3">
            <label class="tich-check">
                <input type="checkbox" name="notify_students" value="1">
                Send a new notification to all students (even if already published)
            </label>
        </div>
    @endif

    <button type="submit" class="tich-btn tich-btn-primary tich-mt-6">{{ $isEdit ? 'Save changes' : 'Publish opportunity' }}</button>
</form>