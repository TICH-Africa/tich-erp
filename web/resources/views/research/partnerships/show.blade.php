@extends('layouts.research')

@section('title', $request->request_number)

@section('research-content')
    <x-page-toolbar title="{{ $request->request_number }}" meta="{{ $request->displayName() }} · {{ ucfirst($request->applicant_type) }}">
        <x-slot:actions>
            <a href="{{ route('research.partnerships.index') }}" class="tich-btn tich-btn-ghost">All inquiries</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif

    <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1.5rem;align-items:start;">
        <section class="research-activity-admin">
            <dl class="tich-text" style="display:grid;gap:0.85rem;">
                <div><dt class="tich-caption">Contact</dt><dd>{{ trim(($request->first_name ?? '').' '.($request->last_name ?? '')) ?: ($request->contact_person ?: '—') }}</dd></div>
                <div><dt class="tich-caption">Email</dt><dd>{{ $request->email }}@if($request->alternative_email)<br><span class="tich-caption">Alt: {{ $request->alternative_email }}</span>@endif</dd></div>
                <div><dt class="tich-caption">Phone</dt><dd>{{ $request->phone ?: '—' }}@if($request->alternative_phone)<br><span class="tich-caption">Alt: {{ $request->alternative_phone }}</span>@endif</dd></div>
                <div><dt class="tich-caption">Research area</dt><dd>{{ $request->research_area ?: '—' }}</dd></div>
                @if ($request->applicant_type === 'organisation')
                    <div>
                        <dt class="tich-caption">Organisation</dt>
                        <dd>
                            {{ $request->organization_name }}
                            ({{ match ($request->organization_type) {
                                'ngo' => 'NGO',
                                'county_government' => 'County government',
                                'academic_institution' => 'Academic institution',
                                'corporate' => 'Corporate',
                                'other' => 'Other',
                                default => $request->organization_type ?: '—',
                            } }})
                        </dd>
                    </div>
                    <div><dt class="tich-caption">Organisation details</dt><dd style="white-space:pre-wrap;">{{ $request->organisation_details }}</dd></div>
                @else
                    <div><dt class="tich-caption">About them</dt><dd style="white-space:pre-wrap;">{{ $request->individual_details }}</dd></div>
                @endif
                <div><dt class="tich-caption">What they do</dt><dd style="white-space:pre-wrap;">{{ $request->what_they_do }}</dd></div>
                <div><dt class="tich-caption">Why partnership</dt><dd style="white-space:pre-wrap;">{{ $request->why_partnership }}</dd></div>
            </dl>

            <h2 class="tich-h3 tich-mt-6">Attachments</h2>
            @forelse ($request->documents as $doc)
                <p class="tich-mt-2">
                    <a href="{{ route('research.partnerships.documents.download', [$request, $doc]) }}" class="tich-link">{{ $doc->original_filename ?: $doc->title }}</a>
                </p>
            @empty
                <p class="tich-caption tich-mt-2">No documents attached.</p>
            @endforelse
        </section>

        <aside>
            <form method="POST" action="{{ route('research.partnerships.status', $request) }}" class="uf-form" data-uf="skip">
                @csrf
                @method('PUT')
                <div class="uf-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        @foreach (['pending_review','under_evaluation','approved','declined'] as $st)
                            <option value="{{ $st }}" @selected($request->status === $st)>{{ str_replace('_', ' ', ucfirst($st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="uf-field tich-mt-4">
                    <label for="review_notes">Review notes</label>
                    <textarea id="review_notes" name="review_notes" rows="5">{{ old('review_notes', $request->review_notes) }}</textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Save status</button>
            </form>
        </aside>
    </div>
@endsection
