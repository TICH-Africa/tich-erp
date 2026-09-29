@extends('layouts.finance')

@section('title', 'Donation Details - ' . $donation->donor_name)

@section('finance-content')
    <x-page-toolbar title="Donation Details" meta="{{ $donation->donor_name }} - KES {{ number_format((float) $donation->amount, 2) }}">
        <x-slot:actions>
            <a href="{{ route('finance.financial-aid.donations') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6" style="gap: 1.5rem;">
        <article class="tich-card" style="grid-column: span 2;">
            <div class="tich-card__body">
                <h3 class="tich-h3 tich-mb-4">Donation Details</h3>
                <dl class="tich-dl tich-mb-6">
                    <dt>Donor Name</dt><dd>{{ $donation->donor_name }}</dd>
                    <dt>Email</dt><dd>{{ $donation->donor_email }}</dd>
                    @if ($donation->donor_phone)
                        <dt>Phone</dt><dd>{{ $donation->donor_phone }}</dd>
                    @endif
                    <dt>Amount</dt><dd>KES {{ number_format((float) $donation->amount, 2) }}</dd>
                    <dt>Designation</dt><dd>{{ ucfirst(str_replace('_', ' ', $donation->designation ?? 'General Fund')) }}</dd>
                    <dt>Type</dt><dd>{{ ucfirst(str_replace('_', ' ', $donation->donation_type)) }}</dd>
                    <dt>Submitted</dt><dd>{{ $donation->created_at->format('d M Y H:i') }}</dd>
                    <dt>Current Status</dt>
                        <dd>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="tich-badge {{ $statusColors[$donation->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($donation->status) }}
                            </span>
                        </dd>
                    @if ($donation->message)
                        <dt>Message</dt>
                        <dd class="tich-prose tich-border-l tich-border-blue-500 tich-pl-4">{{ nl2br(e($donation->message)) }}</dd>
                    @endif
                </dl>

                @if ($donation->confirmed_by)
                    <hr class="tich-my-4">
                    <h4 class="tich-h5 tich-mb-3">Confirmation Details</h4>
                    <dl class="tich-dl">
                        <dt>Confirmed By</dt><dd>{{ $donation->confirmedBy?->name ?? 'Unknown' }}</dd>
                        <dt>Confirmed At</dt><dd>{{ $donation->confirmed_at?->format('d M Y H:i') }}</dd>
                        @if ($donation->transaction_reference)
                            <dt>Transaction Ref</dt><dd>{{ $donation->transaction_reference }}</dd>
                        @endif
                        @if ($donation->confirmation_notes)
                            <dt>Notes</dt><dd>{{ nl2br(e($donation->confirmation_notes)) }}</dd>
                        @endif
                    </dl>
                @endif
            </div>
        </article>

        <aside class="tich-card" style="position: sticky; top: 2rem;">
            <div class="tich-card__body">
                <h3 class="tich-h4 tich-mb-4">Update Status</h3>

                @if ($donation->status === 'pending')
                    <form method="POST" action="{{ route('finance.financial-aid.donations.confirm', $donation) }}">
                        @csrf

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="status">New Status <span class="uf-req">*</span></label>
                            <select id="status" name="status" class="uf-input" required>
                                <option value="confirmed">Confirm</option>
                                <option value="completed">Mark Completed</option>
                                <option value="cancelled">Cancel</option>
                            </select>
                        </div>

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="transaction_reference">Transaction Reference</label>
                            <input type="text" id="transaction_reference" name="transaction_reference" class="uf-input" placeholder="M-Pesa code, bank ref, etc.">
                        </div>

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="confirmation_notes">Notes</label>
                            <textarea id="confirmation_notes" name="confirmation_notes" rows="3" class="uf-input" placeholder="Internal notes"></textarea>
                        </div>

                        <button type="submit" class="uf-btn uf-btn-success">Update Status</button>
                    </form>
                @elseif ($donation->status === 'confirmed')
                    <form method="POST" action="{{ route('finance.financial-aid.donations.confirm', $donation) }}">
                        @csrf
                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="status">New Status <span class="uf-req">*</span></label>
                            <select id="status" name="status" class="uf-input" required>
                                <option value="completed">Mark Completed</option>
                                <option value="cancelled">Cancel</option>
                            </select>
                        </div>
                        <button type="submit" class="uf-btn uf-btn-success">Mark Completed</button>
                    </form>
                @elseif ($donation->status === 'cancelled')
                    <div class="tich-alert tich-alert--error">
                        <p class="tich-text tich-mb-0">This donation has been cancelled.</p>
                    </div>
                @else
                    <div class="tich-alert tich-alert--success">
                        <p class="tich-text tich-mb-0">Donation completed.</p>
                    </div>
                @endif
            </div>
        </aside>
    </div>
@endsection