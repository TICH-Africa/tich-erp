@extends('layouts.app')

@section('title', 'Data subject request')

@section('content')
    <article class="tich-content-page tich-container" style="max-width: 42rem; margin: 2rem auto;">
        <h1 class="tich-h2">Data subject request</h1>
        <p class="tich-text tich-mt-4">
            Use this form to exercise your rights under the <strong>Kenya Data Protection Act, 2019</strong>.
            See our <a href="{{ route('privacy') }}" class="tich-link">Privacy Policy</a> for details.
        </p>

        @include('partials.alerts')

        <form method="POST" action="{{ route('privacy.data-request.store') }}" class="tich-mt-6">
            @csrf

            <div class="uf-field">
                <label for="requester_name">Your full name <span class="uf-req">*</span></label>
                <input type="text" id="requester_name" name="requester_name" class="tich-input" required value="{{ old('requester_name') }}">
                @error('requester_name')<p class="tich-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="uf-field">
                <label for="requester_email">Email <span class="uf-req">*</span></label>
                <input type="email" id="requester_email" name="requester_email" class="tich-input" required value="{{ old('requester_email') }}">
                @error('requester_email')<p class="tich-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="uf-field">
                <label for="requester_phone">Phone (optional)</label>
                <input type="text" id="requester_phone" name="requester_phone" class="tich-input" value="{{ old('requester_phone') }}">
            </div>

            <div class="uf-field">
                <label for="request_type">Request type <span class="uf-req">*</span></label>
                <select id="request_type" name="request_type" class="tich-input" required>
                    <option value="">Select</option>
                    @foreach ($requestTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('request_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('request_type')<p class="tich-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="uf-field">
                <label for="description">Details</label>
                <textarea id="description" name="description" class="tich-input" rows="5" placeholder="Tell us what you need and how we can identify your records (e.g. application number, employee number).">{{ old('description') }}</textarea>
            </div>

            <button type="submit" class="tich-btn tich-btn-primary">Submit request</button>
        </form>
    </article>
@endsection
