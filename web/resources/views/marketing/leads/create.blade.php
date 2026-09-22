@extends('layouts.marketing')

@section('title', 'Add Lead')

@section('department-content')
    <x-page-toolbar title="Add Lead" meta="Record a new prospect" />

    <form method="POST" action="{{ route('marketing.leads.store') }}" enctype="multipart/form-data" class="tich-blog-compose" data-blog-compose style="padding: 1rem;">
        @csrf
        <div class="tich-form-grid tich-form-grid--2" style="gap: 1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="name">Name</label>
                <input id="name" type="text" name="name" class="tich-input" required maxlength="300">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="email">Email</label>
                <input id="email" type="email" name="email" class="tich-input" maxlength="255">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="phone">Phone</label>
                <input id="phone" type="text" name="phone" class="tich-input" maxlength="50">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="source">Source</label>
                <select id="source" name="source" class="tich-input">
                    <option value="referral">Referral</option>
                    <option value="social_media">Social Media</option>
                    <option value="website">Website</option>
                    <option value="event">Event</option>
                    <option value="direct">Direct</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="stage">Stage</label>
                <select id="stage" name="stage" class="tich-input" required>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="qualified">Qualified</option>
                    <option value="proposal">Proposal</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="program_id">Programme</label>
                <select id="program_id" name="program_id" class="tich-input">
                    <option value="">All Programmes</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="intake">Intake</label>
                <input id="intake" type="text" name="intake" class="tich-input" maxlength="100">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="next_followup">Next Followup</label>
                <input id="next_followup" type="date" name="next_followup" class="tich-input">
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" class="tich-input" rows="3"></textarea>
            </div>
        </div>
        <div class="tich-mt-4 tich-blog-compose__footer">
            <a href="{{ route('marketing.leads.index') }}" class="tich-btn tich-btn-secondary">Cancel</a>
            <button type="submit" class="tich-btn tich-btn-primary">Save Lead</button>
        </div>
    </form>
@endsection
