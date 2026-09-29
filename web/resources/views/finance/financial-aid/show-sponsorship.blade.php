@extends('layouts.finance')

@section('title', 'Sponsorship Inquiry - ' . $inquiry->sponsor_name)

@section('finance-content')
    <x-page-toolbar title="Sponsorship Inquiry" meta="{{ $inquiry->sponsor_name }} - {{ ucfirst(str_replace('_', ' ', $inquiry->sponsor_type)) }}">
        <x-slot:actions>
            <a href="{{ route('finance.financial-aid.sponsorships') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6" style="gap: 1.5rem;">
        <article class="tich-card" style="grid-column: span 2;">
            <div class="tich-card__body">
                <h3 class="tich-h3 tich-mb-4">Inquiry Details</h3>
                <dl class="tich-dl tich-mb-6">
                    <dt>Sponsor Name</dt><dd>{{ $inquiry->sponsor_name }}</dd>
                    <dt>Email</dt><dd>{{ $inquiry->sponsor_email }}</dd>
                    <dt>Phone</dt><dd>{{ $inquiry->sponsor_phone }}</dd>
                    <dt>Sponsorship Type</dt><dd>{{ ucfirst(str_replace('_', ' ', $inquiry->sponsor_type)) }}</dd>
                    <dt>Preferred Duration</dt><dd>{{ $inquiry->duration === 'ongoing' ? 'Ongoing / Annual Renewal' : $inquiry->duration . ' Year(s)' }}</dd>
                    <dt>Preferred Field</dt><dd>{{ $inquiry->preferred_field ?? 'Any / Most Needed' }}</dd>
                    <dt>Submitted</dt><dd>{{ $inquiry->created_at->format('d M Y H:i') }}</dd>
                    <dt>Current Status</dt>
                        <dd>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'contacted' => 'bg-blue-100 text-blue-800',
                                    'in_progress' => 'bg-purple-100 text-purple-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'declined' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="tich-badge {{ $statusColors[$inquiry->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($inquiry->status) }}
                            </span>
                        </dd>
                    @if ($inquiry->sponsor_message)
                        <dt>Message</dt>
                        <dd class="tich-prose tich-border-l tich-border-blue-500 tich-pl-4">{{ nl2br(e($inquiry->sponsor_message)) }}</dd>
                    @endif
                </dl>

                @if ($inquiry->status !== 'pending')
                    <hr class="tich-my-4">
                    <h4 class="tich-h5 tich-mb-3">Follow-up History</h4>
                    <dl class="tich-dl">
                        @if ($inquiry->status === 'contacted')
                            <dt>Contacted</dt><dd>Contact initiated with sponsor</dd>
                        @elseif ($inquiry->status === 'in_progress')
                            <dt>In Progress</dt><dd>Sponsorship agreement being finalized</dd>
                        @elseif ($inquiry->status === 'completed')
                            <dt>Completed</dt><dd>Sponsorship agreement signed and active</dd>
                        @elseif ($inquiry->status === 'declined')
                            <dt>Declined</dt><dd>Sponsor declined or inquiry not pursued</dd>
                        @endif
                    </dl>
                @endif
            </div>
        </article>

        <aside class="tich-card" style="position: sticky; top: 2rem;">
            <div class="tich-card__body">
                <h3 class="tich-h4 tich-mb-4">Update Status</h3>

                @if ($inquiry->status === 'pending')
                    <form method="POST" action="{{ route('finance.financial-aid.sponsorships.update', $inquiry) }}">
                        @csrf
                        @method('PUT')

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="status">Update Status <span class="uf-req">*</span></label>
                            <select id="status" name="status" class="uf-input" required>
                                <option value="contacted">Contact Initiated</option>
                                <option value="in_progress">In Progress</option>
                                <option value="declined">Declined</option>
                            </select>
                        </div>

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="followup_notes">Follow-up Notes</label>
                            <textarea id="followup_notes" name="followup_notes" rows="3" class="uf-input" placeholder="Notes from conversation with sponsor"></textarea>
                        </div>

                        <button type="submit" class="uf-btn uf-btn-primary">Update Status</button>
                    </form>
                @elseif ($inquiry->status === 'contacted')
                    <form method="POST" action="{{ route('finance.financial-aid.sponsorships.update', $inquiry) }}">
                        @csrf
                        @method('PUT')
                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="status">Update Status <span class="uf-req">*</span></label>
                            <select id="status" name="status" class="uf-input" required>
                                <option value="in_progress">Move to In Progress</option>
                                <option value="declined">Declined</option>
                            </select>
                        </div>
                        <button type="submit" class="uf-btn uf-btn-primary">Move to In Progress</button>
                    </form>
                @elseif ($inquiry->status === 'in_progress')
                    <form method="POST" action="{{ route('finance.financial-aid.sponsorships.update', $inquiry) }}">
                        @csrf
                        @method('PUT')
                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="status">Update Status <span class="uf-req">*</span></label>
                            <select id="status" name="status" class="uf-input" required>
                                <option value="completed">Mark Completed</option>
                                <option value="declined">Declined</option>
                            </select>
                        </div>
                        <button type="submit" class="uf-btn uf-btn-success">Mark Completed</button>
                    </form>
                @elseif ($inquiry->status === 'completed')
                    <div class="tich-alert tich-alert--success">
                        <h4 class="tich-h5 tich-mb-2">Sponsorship Completed</h4>
                        <p class="tich-text tich-mb-0">Sponsorship agreement finalized and active.</p>
                    </div>
                @elseif ($inquiry->status === 'declined')
                    <div class="tich-alert tich-alert--error">
                        <p class="tich-text tich-mb-0">This inquiry was declined.</p>
                    </div>
                @endif
            </div>
        </aside>
    </div>
@endsection