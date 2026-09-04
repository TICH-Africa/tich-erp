@php
    $lifecycleRequests = $lifecycleRequests ?? collect();
    $openDefermentModal = $errors->any() && old('_form') === 'deferment';
@endphp

<x-page-toolbar title="Deferment" meta="Request academic deferment for a defined period">
    <x-slot:actions>
        <button type="button" class="tich-btn tich-btn-primary" data-open-modal="deferment-request-modal">
            Apply for deferment
        </button>
    </x-slot:actions>
</x-page-toolbar>

<section class="tich-portal-panel tich-mt-8">
    <div class="tich-portal-panel__head">
        <h2 class="tich-h3">Your deferment requests</h2>
    </div>
    <div class="tich-card tich-table-panel tich-mt-4">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Registrar</th>
                        <th>Dean</th>
                        <th>Submitted</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lifecycleRequests as $item)
                        <tr>
                            <td>{{ $item->deferment_months ? $item->deferment_months.' month'.($item->deferment_months === 1 ? '' : 's') : '-' }}</td>
                            <td>{{ $item->statusLabel() }}</td>
                            <td>{{ \App\Models\StudentLifecycleRequest::REVIEW_STATUSES[$item->registrar_status ?? 'pending'] ?? ucfirst($item->registrar_status ?? 'pending') }}</td>
                            <td>{{ \App\Models\StudentLifecycleRequest::REVIEW_STATUSES[$item->dean_status ?? 'pending'] ?? ucfirst($item->dean_status ?? 'pending') }}</td>
                            <td class="tich-caption">{{ $item->created_at?->format('d M Y') }}</td>
                            <td class="tich-caption">{{ $item->reviewer_notes ?: \Illuminate\Support\Str::limit($item->reason, 80) }}</td>
                        </tr>
                    @empty
                        @include('partials.states.table-empty', ['colspan' => 6, 'title' => 'No deferment requests yet', 'icon' => 'inbox'])
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<div
    id="deferment-request-modal"
    class="tich-modal{{ $openDefermentModal ? ' is-open' : '' }}"
    aria-hidden="{{ $openDefermentModal ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="deferment-request-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="deferment-request-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 32rem;">
        <header class="tich-modal__header">
            <h2 id="deferment-request-modal-title" class="tich-h3" style="margin:0;">Apply for deferment</h2>
            <button type="button" class="tich-modal__close" data-close-modal="deferment-request-modal" aria-label="Close">&times;</button>
        </header>
        <form method="POST" action="{{ route('portal.lifecycle-requests.store') }}" enctype="multipart/form-data" class="tich-modal__body">
            @csrf
            <input type="hidden" name="_form" value="deferment">

            @if ($errors->any() && old('_form') === 'deferment')
                <div class="tich-modal__errors tich-mb-4">
                    <ul style="margin:0; padding-left:1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="tich-caption" style="margin-top:0;">
                Your request will be reviewed by both the Academic Registrar and the Dean of Students. Both must approve.
            </p>

            <div style="display:grid; gap:1rem; margin-top:1rem;">
                <div class="tich-form-group" style="margin:0;">
                    <label for="deferment_months" class="tich-label">Deferment period (months)</label>
                    <input id="deferment_months" name="deferment_months" type="number" min="1" max="36" class="tich-input" value="{{ old('deferment_months') }}" required>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label for="deferment_reason" class="tich-label">Reason</label>
                    <textarea id="deferment_reason" name="reason" rows="4" class="tich-input" required>{{ old('reason') }}</textarea>
                </div>
                <div class="tich-form-group" style="margin:0;">
                    <label for="deferment_attachments" class="tich-label">Supporting document(s)</label>
                    <input id="deferment_attachments" name="attachments[]" type="file" class="tich-input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple required>
                    <p class="tich-caption">PDF, Word, or image. Max 10&nbsp;MB each (up to 5 files).</p>
                </div>
            </div>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="deferment-request-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Submit request</button>
            </footer>
        </form>
    </div>
</div>

@include('admin.partials.tich-modal-assets')
