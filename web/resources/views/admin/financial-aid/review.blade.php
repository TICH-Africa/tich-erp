@extends('layouts.admin')

@section('title', 'Review Application - ' . $application->student_name)

@section('content')
    <x-page-toolbar title="Review Application" meta="{{ $application->student_name }} - {{ $application->opportunity->title }}">
        <x-slot:actions>
            <a href="{{ route('admin.financial-aid.applications', $application->opportunity) }}" class="tich-btn tich-btn-ghost">Back to Applications</a>
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
                        <dt>Program Applied For</dt><dd>{{ $application->program_applied }}</dd>
                    @endif
                    <dt>Submitted</dt><dd>{{ $application->created_at->format('d M Y H:i') }}</dd>
                    <dt>Current Status</dt>
                        <dd>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'under_review' => 'bg-blue-100 text-blue-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'allocated' => 'bg-purple-100 text-purple-800',
                                ];
                            @endphp
                            <span class="tich-badge {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $application->status)) }}
                            </span>
                        </dd>
                </dl>

                @if ($application->personal_statement)
                    <h4 class="tich-h5 tich-mb-3">Personal Statement</h4>
                    <div class="tich-prose tich-border-l tich-border-blue-500 tich-pl-4 tich-mb-6">
                        {!! nl2br(e($application->personal_statement)) !!}
                    </div>
                @endif

                @if ($application->financial_need_statement)
                    <h4 class="tich-h5 tich-mb-3">Financial Need Statement</h4>
                    <div class="tich-prose tich-border-l tich-border-green-500 tich-pl-4 tich-mb-6">
                        {!! nl2br(e($application->financial_need_statement)) !!}
                    </div>
                @endif

                @if ($application->supporting_documents && count($application->supporting_documents) > 0)
                    <h4 class="tich-h5 tich-mb-3">Supporting Documents</h4>
                    <ul class="tich-list">
                        @foreach ($application->supporting_documents as $doc)
                            <li>
                                <a href="{{ asset('storage/' . $doc['path']) }}" target="_blank" class="tich-link">
                                    {{ $doc['name'] }} ({{ number_format($doc['size'] / 1024, 1) }} KB)
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </article>

        <aside class="tich-card" style="position: sticky; top: 2rem;">
            <div class="tich-card__body">
                <h3 class="tich-h4 tich-mb-4">Review Actions</h3>

                @if ($application->status === 'pending' || $application->status === 'under_review')
                    <form method="POST" action="{{ route('admin.financial-aid.update-application', $application) }}">
                        @csrf
                        @method('PUT')

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="status">Update Status</label>
                            <select id="status" name="status" class="uf-input" required>
                                <option value="under_review" @selected($application->status === 'under_review')>Under Review</option>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>

                        <div class="uf-form-group tich-mb-4" id="approved_amount_wrap" @style(['display:none' => $application->status !== 'approved'] )>
                            <label class="uf-label" for="approved_amount">Approved Amount (KES) <span class="uf-req">*</span></label>
                            <input type="number" id="approved_amount" name="approved_amount" step="0.01" min="0" class="uf-input" value="{{ old('approved_amount', $application->opportunity->amount ?? '') }}">
                        </div>

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="admin_notes">Admin Notes (visible to student)</label>
                            <textarea id="admin_notes" name="admin_notes" rows="3" class="uf-input">{{ old('admin_notes', $application->admin_notes) }}</textarea>
                        </div>

                        <button type="submit" class="uf-btn uf-btn-primary">Update Status</button>
                    </form>

                    <script>
                        document.getElementById('status').addEventListener('change', function() {
                            var wrap = document.getElementById('approved_amount_wrap');
                            if (wrap) {
                                wrap.style.display = this.value === 'approved' ? '' : 'none';
                            }
                        });
                    </script>
                @elseif ($application->status === 'approved' && $application->allocation_status !== 'allocated')
                    <form method="POST" action="{{ route('admin.financial-aid.allocate', $application) }}">
                        @csrf

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="allocated_amount">Allocate Amount (KES)</label>
                            <input type="number" id="allocated_amount" name="allocated_amount" step="0.01" min="0" max="{{ $application->approved_amount ?? $application->opportunity->amount }}" class="uf-input" required>
                        </div>

                        <div class="uf-form-group tich-mb-4">
                            <label class="uf-label" for="allocation_notes">Allocation Notes</label>
                            <textarea id="allocation_notes" name="allocation_notes" rows="2" class="uf-input"></textarea>
                        </div>

                        <button type="submit" class="uf-btn uf-btn-success">Allocate Funds</button>
                    </form>
                @elseif ($application->status === 'allocated')
                    <div class="tich-alert tich-alert--success">
                        <h4 class="tich-h5 tich-mb-2">Funds Allocated</h4>
                        <p class="tich-text tich-mb-0">Amount: KES {{ number_format((float) $application->approved_amount, 2) }}</p>
                        <p class="tich-text tich-text--sm tich-text--muted tich-mt-1 tich-mb-0">Allocated by {{ $application->allocator?->name ?? 'Unknown' }} on {{ $application->allocated_at?->format('d M Y H:i') }}</p>
                    </div>
                @else
                    <p class="tich-text tich-text--muted">Application is {{ $application->status }}. No further action available.</p>
                @endif

                @if ($application->admin_notes)
                    <hr class="tich-my-4">
                    <h4 class="tich-h5 tich-mb-2">Admin Notes</h4>
                    <div class="tich-prose tich-border-l tich-border-gray-400 tich-pl-4">
                        {!! nl2br(e($application->admin_notes)) !!}
                    </div>
                @endif
            </div>
        </aside>
    </div>
@endsection