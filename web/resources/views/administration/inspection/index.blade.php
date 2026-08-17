@extends('layouts.administration')

@section('title', 'Inspection readiness')

@section('administration-content')
    <x-page-toolbar title="Inspection readiness" meta="On-demand digital dashboard for regulatory audits and legal compliance verification">
        <x-slot:actions>
            <button type="button" class="tich-btn tich-btn-primary" data-open-modal="inspection-create-modal">+ Checklist item</button>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-stat-row tich-stat-row--4 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Readiness score</p>
            <p class="tich-stat__value">{{ number_format($readiness['score'], 0) }}%</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Ready items</p>
            <p class="tich-stat__value">{{ $readiness['ready'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Gaps / pending</p>
            <p class="tich-stat__value">{{ $readiness['gaps'] }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Certs expiring</p>
            <p class="tich-stat__value">{{ $readiness['certs_expiring'] }}</p>
        </div>
    </div>

    <div class="tich-card tich-table-panel tich-mt-8">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Area</th>
                        <th>Requirement</th>
                        <th>Regulator</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($checks as $check)
                        <tr>
                            <td><strong>{{ $check->check_code }}</strong></td>
                            <td>{{ $check->area }}</td>
                            <td>{{ $check->requirement }}</td>
                            <td class="tich-caption">{{ $check->regulator ?? '-' }}</td>
                            <td><span class="tich-badge">{{ ucfirst($check->status) }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('administration.inspection.status', $check) }}" class="tich-flex-wrap" style="gap: 0.35rem;">
                                    @csrf
                                    <select name="status" class="tich-input" style="width: auto;">
                                        @foreach (['pending', 'ready', 'gap', 'waived'] as $status)
                                            <option value="{{ $status }}" @selected($check->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="tich-btn tich-btn-ghost">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="tich-table-empty">No inspection checklist items yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($checks instanceof \Illuminate\Contracts\Pagination\Paginator && $checks->hasPages())
            <div class="tich-mt-4">{{ $checks->links() }}</div>
        @endif
    </div>

    <div id="inspection-create-modal" class="tich-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="tich-modal__backdrop" data-close-modal="inspection-create-modal"></div>
        <div class="tich-modal__dialog">
            <header class="tich-modal__header">
                <h2 class="tich-h3" style="margin:0;">Add inspection item</h2>
                <button type="button" class="tich-modal__close" data-close-modal="inspection-create-modal">&times;</button>
            </header>
            <form method="POST" action="{{ route('administration.inspection.store') }}" class="tich-modal__body">
                @csrf
                <div class="tich-form-stack">
                    <div class="tich-form-group">
                        <label class="tich-label">Area</label>
                        <input type="text" name="area" class="tich-input" required placeholder="e.g. Student records">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Requirement</label>
                        <input type="text" name="requirement" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Regulator</label>
                        <select name="regulator" class="tich-input">
                            <option value="">Optional</option>
                            <option value="KRA">KRA</option>
                            <option value="TVETA">TVETA</option>
                            <option value="MoE">MoE</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Status</label>
                        <select name="status" class="tich-input" required>
                            <option value="pending">Pending</option>
                            <option value="ready">Ready</option>
                            <option value="gap">Gap</option>
                            <option value="waived">Waived</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Notes</label>
                        <textarea name="notes" class="tich-input" rows="2"></textarea>
                    </div>
                </div>
                <footer class="tich-modal__footer">
                    <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="inspection-create-modal">Cancel</button>
                    <button type="submit" class="tich-btn tich-btn-primary">Save</button>
                </footer>
            </form>
        </div>
    </div>

    @include('admin.partials.tich-modal-assets')
@endsection
