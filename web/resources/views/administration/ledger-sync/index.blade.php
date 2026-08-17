@extends('layouts.administration')

@section('title', 'QuickBooks ledger sync')

@section('administration-content')
    <x-page-toolbar title="QuickBooks ledger sync" meta="Synchronize digital payments into QuickBooks for reporting and bank reconciliation">
        <x-slot:actions>
            <form method="POST" action="{{ route('administration.ledger-sync.run') }}">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary" @disabled(! $enabled)>Run sync</button>
            </form>
        </x-slot:actions>
    </x-page-toolbar>

    @error('sync')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <article class="tich-card tich-mt-8">
        <p class="tich-text" style="margin:0;">
            Sync is currently
            <strong>{{ $enabled ? 'enabled' : 'disabled' }}</strong>.
            Configure <code>QUICKBOOKS_ENABLED</code>, client ID/secret, and realm ID in <code>.env</code>.
        </p>
    </article>

    <div class="tich-card tich-table-panel tich-mt-8">
        <h2 class="tich-h3">Sync log</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Source</th>
                        <th>External ref</th>
                        <th>Status</th>
                        <th>Synced at</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td><strong>{{ $log->sync_batch }}</strong></td>
                            <td class="tich-caption">{{ $log->source_type }} #{{ $log->source_id }}</td>
                            <td class="tich-caption">{{ $log->external_ref ?? '-' }}</td>
                            <td><span class="tich-badge">{{ ucfirst($log->status) }}</span></td>
                            <td class="tich-caption">{{ $log->synced_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="tich-caption">{{ \Illuminate\Support\Str::limit($log->error_message, 80) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="tich-table-empty">No sync activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs instanceof \Illuminate\Contracts\Pagination\Paginator && $logs->hasPages())
            <div class="tich-mt-4">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
