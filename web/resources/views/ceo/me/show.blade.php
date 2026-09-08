@extends('layouts.ceo')

@section('title', 'M&E report review')

@section('ceo-content')
    <x-page-toolbar
        :title="($report->department?->dept_name ?? 'Department').' · '.($report->quarter?->label() ?? '')"
        meta="Executive review and digital signature"
    >
        <x-slot:actions>
            <a href="{{ route('ceo.me.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    @endif

    <article class="tich-card tich-mt-6">
        <p class="tich-text">Verified by M&amp;E {{ $report->me_verified_at?->format('d M Y H:i') }}</p>
        @if ($report->me_notes)
            <p class="tich-caption tich-mt-2">{{ $report->me_notes }}</p>
        @endif
        <p class="tich-caption tich-mt-2">
            Achievement: {{ $report->achievementRate() !== null ? number_format($report->achievementRate(), 1).'%' : 'n/a' }}
        </p>
    </article>

    <div class="tich-card tich-table-panel tich-mt-6">
        <div class="tich-table-wrap">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Output</th>
                        <th>Activity</th>
                        <th>Costable item</th>
                        <th>Planned</th>
                        <th>Achieved</th>
                        <th>Deviation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->lines as $line)
                        <tr @if($line->isWarning()) style="background:#fff7ed;" @endif>
                            <td>{{ $line->output }}</td>
                            <td>{{ $line->activity }}</td>
                            <td>{{ $line->costable_item }}</td>
                            <td>{{ number_format((float) $line->planned, 2) }}</td>
                            <td>{{ number_format((float) $line->achieved, 2) }}</td>
                            <td>{{ number_format((float) $line->deviation, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if (! $report->ceo_reviewed_at)
        <form method="POST" action="{{ route('ceo.me.sign', $report) }}" class="tich-card tich-form-stack tich-mt-6">
            @csrf
            <h2 class="tich-h3">Executive digital signature</h2>
            <div class="tich-form-group">
                <label class="tich-label" for="ceo_signature">Signature *</label>
                <input type="text" id="ceo_signature" name="ceo_signature" class="tich-input" required maxlength="300" placeholder="Type full name as digital signature" value="{{ old('ceo_signature') }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="ceo_notes">Strategic notes</label>
                <textarea id="ceo_notes" name="ceo_notes" class="tich-input" rows="3" maxlength="3000">{{ old('ceo_notes') }}</textarea>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Sign and close review</button>
        </form>
    @else
        <article class="tich-card tich-mt-6">
            <p class="tich-text">Signed by CEO {{ $report->ceo_reviewed_at->format('d M Y H:i') }} — {{ $report->ceo_signature }}</p>
            @if ($report->ceo_notes)
                <p class="tich-caption tich-mt-2">{{ $report->ceo_notes }}</p>
            @endif
        </article>
    @endif
@endsection
