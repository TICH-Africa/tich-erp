@extends('layouts.ceo')

@section('title', 'Review curriculum')

@section('ceo-content')
    <x-page-toolbar
        title="Review curriculum"
        meta="{{ $version->program?->program_code }} — {{ $version->intakeLabel() }}"
    >
        <x-slot:actions>
            <a href="{{ route('ceo.curriculum.index') }}" class="tich-btn tich-btn-ghost">Back to list</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('curriculum')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Intake details</h2>
        <div class="tich-grid tich-grid--3 tich-mt-4" style="gap:1rem;">
            <div>
                <p class="tich-caption">Programme</p>
                <p><strong>{{ $version->program?->program_name }}</strong></p>
                <p class="tich-caption">{{ $version->program?->program_code }}</p>
            </div>
            <div>
                <p class="tich-caption">Department</p>
                <p>{{ $version->program?->department?->dept_name ?? '-' }}</p>
            </div>
            <div>
                <p class="tich-caption">Status</p>
                <p><span class="tich-badge">{{ str_replace('_', ' ', $version->status) }}</span></p>
            </div>
            <div>
                <p class="tich-caption">Intake</p>
                <p>{{ $version->intakeLabel() }}</p>
            </div>
            <div>
                <p class="tich-caption">Academic year</p>
                <p>{{ $version->academicYear?->year_label ?? $version->academicYear?->name ?? '-' }}</p>
            </div>
            <div>
                <p class="tich-caption">Registrar approved</p>
                <p>{{ $version->registrar_approved_at?->format('d M Y H:i') ?? '-' }}</p>
            </div>
        </div>
        @if ($version->notes)
            <div class="tich-mt-4">
                <p class="tich-caption">Notes</p>
                <p>{{ $version->notes }}</p>
            </div>
        @endif
    </div>

    <div class="tich-card tich-table-panel tich-mt-6">
        <h2 class="tich-h3">Units in this intake</h2>
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Unit</th>
                        <th>Compulsory</th>
                        <th>Credit hours</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($version->items as $item)
                        <tr>
                            <td>{{ $item->semester ?? '-' }}</td>
                            <td>
                                <strong>{{ $item->unit?->unit_code ?? '-' }}</strong>
                                <p class="tich-caption">{{ $item->unit?->unit_name ?? '' }}</p>
                            </td>
                            <td>{{ $item->is_compulsory ? 'Yes' : 'No' }}</td>
                            <td>{{ $item->credit_hours ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="tich-table-empty">No units mapped on this version.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canApprove)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">CEO publication</h2>
            <p class="tich-text tich-mt-2">Publishing this intake makes it the active curriculum for student enrolment and teaching.</p>
            <form method="POST" action="{{ route('ceo.curriculum.approve', $version) }}" class="tich-mt-4" onsubmit="return confirm('Publish this curriculum version?')">
                @csrf
                <button type="submit" class="tich-btn tich-btn-primary">Approve &amp; publish</button>
            </form>
        </div>
    @elseif ($version->status === 'published')
        <div class="tich-alert tich-alert--success tich-mt-6">
            <strong>Published.</strong>
            @if ($version->ceo_approved_at)
                Approved on {{ $version->ceo_approved_at->format('d M Y H:i') }}.
            @endif
        </div>
    @endif
@endsection
