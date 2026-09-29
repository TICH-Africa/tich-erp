@extends('layouts.finance')

@section('title', 'Allocate Financial Aid - ' . $application->student_name)

@section('finance-content')
    <x-page-toolbar title="Allocate Financial Aid" meta="{{ $application->student_name }} - {{ $application->opportunity->title }}">
        <x-slot:actions>
            <a href="{{ route('finance.financial-aid.applications') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-grid tich-grid--3 tich-mt-6" style="gap: 1.5rem;">
        <article class="tich-card" style="grid-column: span 2;">
            <div class="tich-card__body">
                <h3 class="tich-h3 tich-mb-4">Application Details</h3>
                <dl class="tich-dl tich-mb-6">
                    <dt>Student Name</dt><dd>{{ $application->student_name }}</dd>
                    <dt>Email</dt><dd>{{ $application->student_email }}</dd>
                    @if ($application->student_phone)
                        <dt>Phone</dt><dd>{{ $application->student_phone }}</dd>
                    @endif
                    @if ($application->student_number)
                        <dt>Student/ID Number</dt><dd>{{ $application->student_number }}</dd>
                    @endif
                    @if ($application->program_applied)
                        <dt>Program</dt><dd>{{ $application->program_applied }}</dd>
                    @endif
                    <dt>Opportunity</dt><dd>{{ $application->opportunity->title }}</dd>
                    <dt>Funding Type</dt><dd>{{ ucfirst(str_replace('_', ' ', $application->opportunity->funding_type)) }}</dd>
                    <dt>Approved Amount</dt><dd>KES {{ number_format((float) $application->approved_amount, 2) }}</dd>
                    <dt>Approved Date</dt><dd>{{ $application->approved_at?->format('d M Y') }}</dd>
                    <dt>Current Allocation Status</dt>
                        <dd>
                            @php
                                $allocColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'allocated' => 'bg-green-100 text-green-800',
                                    'partial' => 'bg-blue-100 text-blue-800',
                                ];
                            @endphp
                            <span class="tich-badge {{ $allocColors[$application->allocation_status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($application->allocation_status) }}
                            </span>
                        </dd>
                </dl>

                @if ($application->personal_statement)
                    <h4 class="tich-h5 tich-mb-3">Personal Statement</h4>
                    <div class="tich-prose tich-border-l tich-border-blue-500 tich-pl-4 tich-mb-6">
                        {!! nl2br(e($application->personal_statement)) !!}
                    </div>
                @endif
            </div>
        </article>

        <aside class="tich-card" style="position: sticky; top: 2rem;">
            <div class="tich-card__body">
                <h3 class="tich-h4 tich-mb-4">Allocate Funds</h3>

                @if ($application->allocation_status === 'allocated')
                    <div class="tich-alert tich-alert--success">
                        <h4 class="tich-h5 tich-mb-2">Already Allocated</h4>
                        <p class="tich-text tich-mb-0">Amount: KES {{ number_format((float) $application->approved_amount, 2) }}</p>
                        <p class="tich-text tich-text--sm tich-text--muted tich-mt-1 tich-mb-0">Allocated by {{ $application->allocator?->name ?? 'Unknown' }} on {{ $application->allocated_at?->format('d M Y H:i') }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('finance.financial-aid.applications.allocate', $application) }}">
                        @csrf

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="allocation_amount">Allocation Amount (KES) <span class="uf-req">*</span></label>
                            <input type="number" id="allocation_amount" name="allocation_amount" step="0.01" min="0" max="{{ $application->approved_amount }}" class="uf-input" required value="{{ $application->approved_amount }}">
                            <p class="uf-hint tich-mt-1">Maximum available: KES {{ number_format((float) $application->approved_amount, 2) }}</p>
                        </div>

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="fee_account_id">Student Fee Account (Optional)</label>
                            <select id="fee_account_id" name="fee_account_id" class="uf-input">
                                <option value="">Select fee account</option>
@if ($feeAccount)
                                <option value="{{ $feeAccount->id }}">Account ID: {{ $feeAccount->id }} - Outstanding: KES {{ number_format((float) $feeAccount->outstanding_balance, 2) }}</option>
                            @else
                                    <option value="" disabled>No fee account found for this student</option>
                                @endif
                            </select>
                        </div>

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="allocation_notes">Allocation Notes</label>
                            <textarea id="allocation_notes" name="allocation_notes" rows="3" class="uf-input" placeholder="Internal notes for this allocation"></textarea>
                        </div>

                        <button type="submit" class="uf-btn uf-btn-success">Allocate to Student</button>
                    </form>
                @endif

                @if ($feeAccount)
                    <hr class="tich-my-4">
                    <h4 class="tich-h5 tich-mb-2">Student Fee Account</h4>
                    <dl class="tich-dl">
                        <dt>Account ID</dt><dd>{{ $feeAccount->id }}</dd>
                        <dt>Outstanding Balance</dt><dd>KES {{ number_format((float) $feeAccount->outstanding_balance, 2) }}</dd>
                        @if ($feeAccount->scholarship_amount)
                            <dt>Scholarship Amount</dt><dd>KES {{ number_format((float) $feeAccount->scholarship_amount, 2) }}</dd>
                        @endif
                        @if ($feeAccount->sponsor_amount)
                            <dt>Sponsor Amount</dt><dd>KES {{ number_format((float) $feeAccount->sponsor_amount, 2) }}</dd>
                        @endif
                    </dl>
                @endif
            </div>
        </aside>
    </div>
@endsection