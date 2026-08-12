@extends('layouts.print-document')

@section('document-content')
    @foreach ($statements as $statement)
        <section style="page-break-after: always; margin-bottom: 2rem;">
            <h2 style="font-size:1.05rem; margin:0 0 0.5rem;">{{ $statement['student']->displayName() }}</h2>
            <p style="margin:0 0 1rem; color:#475569; font-size:0.9rem;">
                {{ $statement['student']->registration_number }}
                · {{ $statement['student']->program?->program_name ?? 'Programme' }}
                · Outstanding: KES {{ number_format($statement['outstanding'], 2) }}
            </p>
            <table class="tich-doc-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="num">Debit</th>
                        <th class="num">Credit</th>
                        <th class="num">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statement['entries'] as $entry)
                        <tr>
                            <td>{{ $entry['date'] ? \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') : '-' }}</td>
                            <td>{{ $entry['reference'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td class="num">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}</td>
                            <td class="num">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}</td>
                            <td class="num">{{ number_format($entry['running_balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No transactions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endforeach
@endsection
