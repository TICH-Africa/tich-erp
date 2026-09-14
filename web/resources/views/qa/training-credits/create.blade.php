@extends('layouts.qa')

@section('title', 'Training Event')

@section('qa-content')
    <x-page-toolbar title="Register for training event" meta="Enrol staff in capacity-building sessions" />

    <form method="POST" action="{{ route('qa.capacity.register') }}" class="tich-card tich-mt-8 tich-form-stack">
        @csrf

        <div class="tich-grid tich-grid--3">
            <div class="tich-form-group">
                <label class="tich-label" for="session_id">Session</label>
                <select id="session_id" name="session_id" class="tich-input" required>
                    <option value="">Select session</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->title }} - {{ $session->scheduled_at?->format('d M Y') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="staff_id">Staff member</label>
                <select id="staff_id" name="staff_id" class="tich-input" required>
                    <option value="">Select staff</option>
                    @foreach ($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->surname }} ({{ $s->job_title }})</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="status">Status</label>
                <select id="status" name="status" class="tich-input">
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                </select>
            </div>
        </div>

        <div class="tich-form-group">
            <label class="tich-label" for="decline_reason">Decline reason (if declining)</label>
            <textarea id="decline_reason" name="decline_reason" class="tich-input" rows="2" placeholder="Optional"></textarea>
        </div>

        <div class="tich-form-footer">
            <button type="submit" class="tich-btn tich-btn-primary">Enrol</button>
        </div>
    </form>
@endsection
