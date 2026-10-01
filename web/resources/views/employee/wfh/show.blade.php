@extends('layouts.employee')

@section('employee-content')
    <x-page-toolbar title="{{ $wfh->request_code }}" meta="Work from home · {{ $wfh->statusLabel() }}">
        <x-slot:actions>
            <a href="{{ route('employee.wfh.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
            @if (in_array($wfh->status, ['pending_hr', 'returned'], true))
                <form method="POST" action="{{ route('employee.wfh.cancel', $wfh) }}" onsubmit="return confirm('Cancel this request?');">
                    @csrf
                    <button type="submit" class="tich-btn tich-btn-secondary">Cancel request</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('success'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('success') }}</div>
    @endif
    @error('wfh')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card" style="padding:1.25rem;">
            <p class="tich-caption">WFH date</p>
            <p class="tich-h3" style="margin:0.25rem 0;">{{ $wfh->work_date?->format('l, d F Y') }}</p>
            <p class="tich-text tich-text--muted">
                Week {{ $wfh->week_of_month }} of {{ $wfh->work_date?->format('F Y') }}
                · {{ \Illuminate\Support\Str::of($wfh->start_time)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($wfh->end_time)->substr(0, 5) }}
                @if ($wfh->total_hours) ({{ $wfh->total_hours }}h) @endif
            </p>
            <p class="tich-mt-4"><span class="uf-badge">{{ $wfh->statusLabel() }}</span></p>
        </article>
        <article class="tich-card" style="padding:1.25rem;">
            <p class="tich-caption">Department</p>
            <p class="tich-text" style="margin:0.25rem 0;"><strong>{{ $wfh->department_name ?? '-' }}</strong></p>
            <p class="tich-caption">Supervisor</p>
            <p class="tich-text">{{ $wfh->supervisor_name ?? '-' }}</p>
            <p class="tich-caption tich-mt-2">Job title</p>
            <p class="tich-text">{{ $wfh->job_title ?? '-' }}</p>
        </article>
    </div>

    <div class="tich-card tich-mt-6" style="padding:1.25rem;">
        <h2 class="tich-h3">Remote tasks</h2>
        <ol class="tich-mt-2">
            @forelse (($wfh->remote_tasks ?? []) as $task)
                <li class="tich-text">{{ $task }}</li>
            @empty
                <li class="tich-text tich-text--muted">No tasks listed.</li>
            @endforelse
        </ol>
    </div>

    @if ($wfh->hr_notes || $wfh->hr_reviewed_at)
        <div class="tich-card tich-mt-6" style="padding:1.25rem;">
            <h2 class="tich-h3">HR decision</h2>
            <p class="tich-text">Reviewed {{ $wfh->hr_reviewed_at?->format('d M Y H:i') }} by {{ $wfh->hrReviewer?->fullName() ?? 'HR' }}</p>
            @if ($wfh->hr_notes)
                <p class="tich-text tich-mt-2">{{ $wfh->hr_notes }}</p>
            @endif
        </div>
    @endif
@endsection
