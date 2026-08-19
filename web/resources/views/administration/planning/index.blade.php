@extends('layouts.administration')

@section('title', 'Multi-tier planning')

@section('administration-content')
    <x-page-toolbar title="Multi-tier planning" meta="Annual, quarterly, monthly, and weekly institutional planning with strict requisition deadlines">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="planning-create-modal">+ New cycle</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Tier</th>
                        <th>Fiscal year</th>
                        <th>Period</th>
                        <th>Requisition deadline</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cycles as $cycle)
                        <tr>
                            <td><strong>{{ $cycle->cycle_code }}</strong></td>
                            <td>{{ $cycle->title }}</td>
                            <td class="tich-caption">{{ ucfirst($cycle->plan_tier) }}</td>
                            <td>{{ $cycle->fiscal_year }}</td>
                            <td class="tich-caption">{{ $cycle->period_start?->format('d M Y') }} - {{ $cycle->period_end?->format('d M Y') }}</td>
                            <td class="tich-caption {{ $cycle->isPastDeadline() ? 'tich-text--danger' : '' }}">
                                {{ $cycle->requisition_deadline?->format('d M Y H:i') }}
                            </td>
                            <td><span class="tich-badge">{{ ucfirst($cycle->status) }}</span></td>
                            <td>
                                @if ($cycle->status === 'open')
                                    <form method="POST" action="{{ route('administration.planning.lock', $cycle) }}" onsubmit="return confirm('Lock this cycle?')">
                                        @csrf
                                        <button type="submit" class="tich-btn tich-btn-ghost">Lock</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="tich-table-empty">No planning cycles yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cycles instanceof \Illuminate\Contracts\Pagination\Paginator && $cycles->hasPages())
            <div class="tich-mt-4">{{ $cycles->links() }}</div>
        @endif
    </div>

    <div id="planning-create-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="planning-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin:0;">Create planning cycle</h2>
                <button type="button" class="tich-modal__close" data-close-modal="planning-create-modal">&times;</button>
            </header>
            <form method="POST" action="{{ route('administration.planning.store') }}" class="tich-modal__body">
                @csrf
                <div class="tich-form-stack">
                    <div class="tich-form-group">
                        <label class="tich-label">Title</label>
                        <input type="text" name="title" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Plan tier</label>
                        <select name="plan_tier" class="tich-input" required>
                            <option value="annual">Annual</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Fiscal year</label>
                        <input type="number" name="fiscal_year" class="tich-input" value="{{ now()->year }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Period start</label>
                        <input type="date" name="period_start" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Period end</label>
                        <input type="date" name="period_end" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Requisition deadline</label>
                        <input type="datetime-local" name="requisition_deadline" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Notes</label>
                        <textarea name="notes" class="tich-input" rows="2"></textarea>
                    </div>
                </div>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="planning-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Create</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
@endsection
