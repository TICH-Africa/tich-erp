@extends('layouts.qa')

@section('title', $flag->flag_number)

@section('qa-content')
    <x-page-toolbar title="{{ $flag->flag_number }} — {{ $flag->category }}" meta="{{ $flag->severity }} severity · {{ str_replace('_', ' ', $flag->status) }}">
        <x-slot:actions>
            @if ($flag->isOpen() || $flag->isInProgress())
                    <form method="POST" action="{{ route('qa.qca-flags.update-status', $flag) }}" class="tich-form-stack" style="display:inline;">
                        @csrf
                        <select name="status" class="tich-input" onchange="this.form.submit()" style="width:auto;">
                            <option value="">Change status...</option>
                            <option value="in_progress" @selected($flag->status === 'open')>In Progress</option>
                            <option value="resolved" @selected($flag->status === 'in_progress')>Resolve</option>
                            <option value="closed" @selected($flag->status === 'resolved')>Close</option>
                        </select>
                    </form>
                    <form method="POST" action="{{ route('qa.qca-flags.resolve', $flag) }}" class="tich-form-stack" style="display:inline;" onsubmit="return confirm('Resolve this flag?');">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-primary tich-btn--sm">Resolve</button>
                    </form>
                @endif
            @if ($flag->isResolved() && $flag->isHighOrCritical())
                    <form method="POST" action="{{ route('qa.qca-flags.close', $flag) }}" onsubmit="return confirm('Close this flag?');">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-secondary tich-btn--sm">Close</button>
                    </form>
                @endif
            @if ($flag->isHighOrCritical() && ($flag->isOpen() || $flag->isInProgress()))
                    <form method="POST" action="{{ route('qa.qca-flags.ceo-override', $flag) }}" onsubmit="return confirm('CEO override: release downstream locks? CEO reason required.');">
                        @csrf
                        <button type="submit" class="tich-btn tich-btn-ghost tich-btn--sm" style="color:#f59e0b;">CEO Override</button>
                    </form>
                @endif
            <a href="{{ route('qa.qca-flags.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('update-status')<div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>@enderror
    @error('resolve')<div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>@enderror
    @error('close')<div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>@enderror
    @error('ceo-override')<div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>@enderror

    <div class="tich-grid tich-grid--4 tich-mt-8">
        <article class="tich-card">
            <p class="tich-caption">Category</p>
            <p class="tich-h2 tich-mt-2">{{ $flag->category }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Severity</p>
            <p class="tich-h2 tich-mt-2">
                <span class="tich-badge tich-badge--{{ strtolower($flag->severity) }}">{{ $flag->severity }}</span>
            </p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Assigned to</p>
            <p class="tich-h2 tich-mt-2">{{ $flag->assignedTo?->first_name.' '.$flag->assignedTo?->surname ?? 'Unassigned' }}</p>
        </article>
        <article class="tich-card">
            <p class="tich-caption">Deadline</p>
            <p class="tich-h2 tich-mt-2">{{ $flag->resolution_deadline?->format('d M Y') ?? '-' }}</p>
        </article>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Description</h2>
        <p class="tich-text tich-mt-2">{{ $flag->description }}</p>
    </div>

    <div class="tich-grid tich-grid--2 tich-mt-6">
        <div class="tich-card">
            <h2 class="tich-h3">Target Entity</h2>
            <p class="tich-text tich-mt-2">
                {{ $flag->target_entity_type ?? '-' }} #{{ $flag->target_entity_id ?? '-' }}
            </p>
            <p class="tich-caption tich-mt-2">Source: {{ $flag->source_module ?? '-' }} #{{ $flag->source_entity_id ?? '-' }}</p>
        </div>
        <div class="tich-card">
            <h2 class="tich-h3">Downstream Locks</h2>
            @if ($flag->isHighOrCritical() && $flag->lockedModules() !== [])
                <ul class="tich-mt-2" style="margin:0;padding-left:1.25rem;">
                    @foreach ($flag->lockedModules() as $module)
                        <li class="tich-text tich-mt-1"><strong>{{ $module }}</strong> — blocked</li>
                    @endforeach
                </ul>
            @else
                <p class="tich-text tich-mt-2">No downstream locks active.</p>
            @endif
        </div>
    </div>

    <div class="tich-card tich-mt-6">
        <h2 class="tich-h3">Resolution</h2>
        @if ($flag->resolution_description)
            <p class="tich-text tich-mt-2">{{ $flag->resolution_description }}</p>
        @else
            <p class="tich-caption tich-mt-2">No resolution recorded yet.</p>
        @endif
        @if ($flag->root_cause)
            <p class="tich-caption tich-mt-2"><strong>Root cause:</strong> {{ $flag->root_cause }}</p>
        @endif
        @if ($flag->evidence_of_correction)
            <p class="tich-caption tich-mt-2"><strong>Evidence of correction:</strong> {{ $flag->evidence_of_correction }}</p>
        @endif
        @if ($flag->resolved_at)
            <p class="tich-caption tich-mt-2">Resolved by {{ $flag->resolvedBy?->first_name.' '.$flag->resolvedBy?->surname ?? 'Unknown' }} on {{ $flag->resolved_at?->format('d M Y H:i') }}</p>
        @endif
    </div>

    @if ($flag->isResolved() && $flag->isHighOrCritical())
        <div class="tich-card tich-card--alert tich-mt-6">
            <h2 class="tich-h3">CEO Override</h2>
            <p class="tich-text tich-mt-2">A CEO override can release downstream locks for this High/Critical flag. This action is fully audit-logged and requires a documented reason.</p>
            <form method="POST" action="{{ route('qa.qca-flags.ceo-override', $flag) }}" class="tich-mt-4" onsubmit="return confirm('Confirm CEO override? CEO must provide a documented reason.');">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="reason">Reason (required)</label>
                    <textarea id="reason" name="reason" class="tich-input" rows="3" required placeholder="Document the reason for lifting the lock"></textarea>
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Apply CEO Override</button>
            </form>
        </div>
    @endif

    <div class="tich-card tich-mt-6">
        <div class="tich-flex tich-flex--between tich-mb-4">
            <h2 class="tich-h3">Milestones</h2>
            <button type="button" class="tich-btn tich-btn-secondary tich-btn--sm" onclick="document.getElementById('milestone-form').style.display='block'">Add milestone</button>
        </div>

        <form id="milestone-form" method="POST" action="{{ route('qa.qca-flags.add-milestone', $flag) }}" class="tich-card tich-mt-4 tich-mb-4" style="display:none;">
            @csrf
            <div class="tich-grid tich-grid--3">
                <div class="tich-form-group">
                    <label class="tich-label" for="milestone_type">Type</label>
                    <select id="milestone_type" name="milestone_type" class="tich-input" required>
                        @foreach (\App\Models\Qa\QcaFlag::MILESTONE_TYPES as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tich-form-group" style="grid-column:span 2;">
                    <label class="tich-label" for="milestone_description">Description</label>
                    <input id="milestone_description" name="description" class="tich-input" required placeholder="Milestone description">
                </div>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="milestone_evidence">Evidence (optional)</label>
                <textarea id="milestone_evidence" name="evidence" class="tich-input" rows="2" placeholder="Link to evidence or notes"></textarea>
            </div>
            <div class="tich-form-footer">
                <button type="submit" class="tich-btn tich-btn-primary">Save milestone</button>
                <button type="button" class="tich-btn tich-btn-ghost" onclick="document.getElementById('milestone-form').style.display='none'">Cancel</button>
            </div>
        </form>

        @if ($flag->milestones->isNotEmpty())
            <ul class="tich-mt-4" style="margin:0;padding-left:1.25rem;">
                @foreach ($flag->milestones as $milestone)
                    <li class="tich-mt-3">
                        <strong>{{ $milestone->milestone_type }}</strong> — {{ $milestone->recordedBy?->first_name.' '.$milestone->recordedBy?->surname ?? 'Unknown' }} · {{ $milestone->created_at?->format('d M Y H:i') }}
                        <p class="tich-caption">{{ $milestone->description }}</p>
                        @if ($milestone->evidence)
                            <p class="tich-caption">Evidence: {{ $milestone->evidence }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="tich-caption tich-mt-4">No milestones recorded yet.</p>
        @endif
    </div>
@endsection
