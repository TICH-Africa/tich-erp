@php
    $opportunity = $opportunity ?? new \App\Models\EfundiOpportunity();
    $scopes = $scopes ?? \App\Models\EfundiOpportunity::scopes();
    $linkableVacancies = $linkableVacancies ?? collect();
    $isEdit = $opportunity->exists;
    $action = $isEdit
        ? route($routePrefix.'.update', $opportunity)
        : route($routePrefix.'.store');
    $selectedScope = old('opportunity_scope', $opportunity->opportunity_scope ?? \App\Models\EfundiOpportunity::SCOPE_EXTERNAL);
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
            <label class="tich-label" for="efundi-scope">Where is this opportunity? <span class="uf-req">*</span></label>
            <select id="efundi-scope" name="opportunity_scope" class="tich-input" required>
                @foreach ($scopes as $value => $label)
                    <option value="{{ $value }}" @selected($selectedScope === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="tich-caption tich-mt-1">Within TICH uses HR careers applications (same as <a href="{{ route('careers.index') }}" target="_blank" rel="noopener">/careers</a>).</p>
        </div>
        <div id="efundi-internal-field" class="@if($selectedScope !== 'internal') hidden @endif">
            <label class="tich-label" for="efundi-vacancy">HR vacancy <span class="uf-req">*</span></label>
            <select id="efundi-vacancy" name="job_vacancy_id" class="tich-input">
                <option value="">Select published vacancy…</option>
                @foreach ($linkableVacancies as $vacancy)
                    <option value="{{ $vacancy->id }}" @selected((int) old('job_vacancy_id', $opportunity->job_vacancy_id) === (int) $vacancy->id)">
                        {{ $vacancy->job_title }} ({{ $vacancy->vacancy_number ?? 'VAC-'.$vacancy->id }}) - {{ $vacancy->department->dept_name ?? 'TICH' }}
                    </option>
                @endforeach
            </select>
            @if ($linkableVacancies->isEmpty())
                <p class="tich-caption tich-mt-1">No open HR vacancies. Ask HR to publish one under <strong>Vacancies</strong> first.</p>
            @endif
        </div>
        <div>
            <label class="tich-label" for="efundi-org">Organization</label>
            <input id="efundi-org" name="organization_name" class="tich-input" maxlength="255" value="{{ old('organization_name', $opportunity->organization_name) }}" placeholder="Leave blank for within TICH">
        </div>
        <div>
            <label class="tich-label" for="efundi-location">Location</label>
            <input id="efundi-location" name="location" class="tich-input" maxlength="255" value="{{ old('location', $opportunity->location) }}">
        </div>
        <div id="efundi-deadline-field">
            <label class="tich-label" for="efundi-deadline">Application deadline</label>
            <input id="efundi-deadline" type="date" name="application_deadline" class="tich-input" value="{{ old('application_deadline', optional($opportunity->application_deadline)->format('Y-m-d')) }}">
            <p class="tich-caption tich-mt-1 efundi-internal-hint @if($selectedScope !== 'internal') hidden @endif">For within TICH, the linked vacancy closing date on HR/careers also applies.</p>
        </div>
    </div>

    <div id="efundi-external-fields" class="tich-grid tich-grid--2 tich-mt-2 @if($selectedScope === 'internal') hidden @endif" style="gap: 1rem;">
        <div>
            <label class="tich-label" for="efundi-url">Application link (URL)</label>
            <input id="efundi-url" type="url" name="application_url" class="tich-input @error('application_url') is-invalid @enderror" placeholder="https://employer.com/apply" inputmode="url" autocomplete="url" value="{{ old('application_url', $opportunity->application_url) }}">
            <p class="tich-caption tich-mt-1">Website address only - must start with http:// or https://</p>
            @error('application_url')
                <p class="uf-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="tich-label" for="efundi-email">Application email</label>
            <input id="efundi-email" type="email" name="application_email" class="tich-input @error('application_email') is-invalid @enderror" placeholder="careers@example.com" inputmode="email" autocomplete="email" value="{{ old('application_email', $opportunity->application_email) }}">
            <p class="tich-caption tich-mt-1">Email address only - not a web link</p>
            @error('application_email')
                <p class="uf-error">{{ $message }}</p>
            @enderror
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

<script>
    (function () {
        const scope = document.getElementById('efundi-scope');
        const internalField = document.getElementById('efundi-internal-field');
        const externalFields = document.getElementById('efundi-external-fields');
        const vacancySelect = document.getElementById('efundi-vacancy');
        const internalHints = document.querySelectorAll('.efundi-internal-hint');

        if (!scope) {
            return;
        }

        const sync = () => {
            const internal = scope.value === 'internal';
            internalField?.classList.toggle('hidden', !internal);
            externalFields?.classList.toggle('hidden', internal);
            internalHints.forEach((el) => el.classList.toggle('hidden', !internal));
            if (vacancySelect) {
                vacancySelect.required = internal;
            }
        };

        scope.addEventListener('change', sync);
        sync();
    })();
</script>