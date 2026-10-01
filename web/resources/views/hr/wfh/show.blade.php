@extends('layouts.hr')

@section('title', $wfh->request_code)

@section('hr-content')
    <x-page-toolbar title="{{ $wfh->request_code }}" meta="Work from home review · {{ $wfh->statusLabel() }}">
        <x-slot:actions>
            <a href="{{ route('hr.wfh.index') }}" class="tich-btn tich-btn-ghost">← Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('wfh')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <article class="tich-card" style="padding:1.25rem;">
            <p class="tich-caption">Employee</p>
            <p class="tich-h3" style="margin:0.25rem 0;">{{ $wfh->staff?->fullName() }}</p>
            <p class="tich-caption">{{ $wfh->staff?->employee_number }} · {{ $wfh->job_title ?? $wfh->staff?->job_title }}</p>
            <p class="tich-text tich-mt-3"><strong>Department:</strong> {{ $wfh->department_name ?? $wfh->staff?->department?->dept_name ?? '-' }}</p>
            <p class="tich-text"><strong>Supervisor:</strong> {{ $wfh->supervisor_name ?? '-' }}</p>
        </article>
        <article class="tich-card" style="padding:1.25rem;">
            <p class="tich-caption">Requested WFH day</p>
            <p class="tich-h3" style="margin:0.25rem 0;">{{ $wfh->work_date?->format('l, d F Y') }}</p>
            <p class="tich-text">
                {{ \Illuminate\Support\Str::of($wfh->start_time)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($wfh->end_time)->substr(0, 5) }}
                @if ($wfh->total_hours) · {{ $wfh->total_hours }} hours @endif
            </p>
            <p class="tich-caption tich-mt-3">Arrangement window</p>
            <p class="tich-text">
                {{ $wfh->period_start?->format('d M Y') ?? '-' }}
                →
                {{ $wfh->period_end?->format('d M Y') ?? '-' }}
            </p>
            @if ($entitlement)
                <p class="tich-caption tich-mt-3">Month bank (through this week)</p>
                <p class="tich-text">{{ $entitlement['used'] }} used · {{ $entitlement['earned'] }} earned · {{ $entitlement['available'] }} remaining after this request slot</p>
            @endif
        </article>
    </div>

    <div class="tich-card tich-mt-6" style="padding:1.25rem;">
        <h2 class="tich-h3">Tasks while working remotely</h2>
        <ol class="tich-mt-2">
            @forelse (($wfh->remote_tasks ?? []) as $task)
                <li class="tich-text">{{ $task }}</li>
            @empty
                <li class="tich-text tich-text--muted">None listed.</li>
            @endforelse
        </ol>
    </div>

    @if ($wfh->isPendingHr())
        <div class="tich-card tich-mt-6" style="padding:1.25rem;">
            <h2 class="tich-h3">Consideration checklist</h2>
            <p class="tich-text tich-text--muted">All items must be Yes to approve (from the WFH request form).</p>

            <form method="POST" action="{{ route('hr.wfh.approve', $wfh) }}" class="tich-mt-4">
                @csrf
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Consideration</th>
                            <th style="width:8rem;">Yes</th>
                            <th style="width:8rem;">No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($considerationLabels as $key => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td>
                                    <label><input type="radio" name="considerations[{{ $key }}]" value="1" required @checked(old('considerations.'.$key, '1') == '1')> Yes</label>
                                </td>
                                <td>
                                    <label><input type="radio" name="considerations[{{ $key }}]" value="0" @checked(old('considerations.'.$key) === '0')> No</label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="tich-form-group tich-mt-4">
                    <label class="tich-label" for="notes">HR notes (optional)</label>
                    <textarea id="notes" name="notes" class="tich-input" rows="3">{{ old('notes') }}</textarea>
                </div>

                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;" class="tich-mt-4">
                    <button type="submit" class="tich-btn tich-btn-primary">Approve</button>
                </div>
            </form>

            <div class="tich-grid tich-grid--2 tich-mt-6" style="gap:1rem;">
                <form method="POST" action="{{ route('hr.wfh.reject', $wfh) }}">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label" for="reject_notes">Reject reason</label>
                        <textarea id="reject_notes" name="notes" class="tich-input" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-secondary">Reject</button>
                </form>
                <form method="POST" action="{{ route('hr.wfh.return', $wfh) }}">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label" for="return_notes">Return for changes</label>
                        <textarea id="return_notes" name="notes" class="tich-input" rows="2" required></textarea>
                    </div>
                    <button type="submit" class="tich-btn tich-btn-ghost">Return to employee</button>
                </form>
            </div>
        </div>
    @else
        <div class="tich-card tich-mt-6" style="padding:1.25rem;">
            <h2 class="tich-h3">Decision</h2>
            <p class="tich-text"><span class="uf-badge">{{ $wfh->statusLabel() }}</span>
                @if ($wfh->hr_reviewed_at)
                    · {{ $wfh->hr_reviewed_at->format('d M Y H:i') }} by {{ $wfh->hrReviewer?->fullName() ?? 'HR' }}
                @endif
            </p>
            @if ($wfh->considerations)
                <ul class="tich-mt-3">
                    @foreach ($considerationLabels as $key => $label)
                        <li class="tich-text">{{ $label }} -
                            <strong>{{ ! empty($wfh->considerations[$key]) ? 'Yes' : 'No' }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if ($wfh->hr_notes)
                <p class="tich-text tich-mt-3">{{ $wfh->hr_notes }}</p>
            @endif
        </div>
    @endif
@endsection
