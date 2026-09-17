@extends('layouts.procurement')

@section('title', 'Payment Audit Trail')

@section('procurement-content')
    <x-page-toolbar title="Payment audit trail" meta="Matching decisions, finance routing, and M-Pesa verification events">
        <x-slot:actions>
            <a href="{{ route('procurement.payment-verification.index') }}" class="tich-btn tich-btn-secondary">Back to overview</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Recent workflow history</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($events as $entry)
                        <tr>
                            <td>{{ $entry->created_at->format('H:i') ?? $entry['time'] ?? '-' }}</td>
                            <td>{{ $entry->actor ?? '-' }}</td>
                            <td>{{ $entry->action ?? '-' }}</td>
                            <td>{{ $entry->result ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $events->links() }}
    </div>

    @if(session('success'))
        <div class="tich-alert tich-alert--success tich-mt-6">{{ session('success') }}</div>
    @endif
@endsection
