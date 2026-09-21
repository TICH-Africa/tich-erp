@extends('layouts.employee')

@section('title', 'Weekly Time Log · '.$log->week_ref)

@section('employee-content')
    <x-page-toolbar title="STAFF WEEKLY TIME LOG" meta="{{ $log->log_code }} · {{ $log->week_ref }}">
        <x-slot:actions>
            <a href="{{ route('employee.time-logs.index') }}" class="tich-btn tich-btn-ghost">All my logs</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if (session('status'))
        <div class="tich-alert tich-alert--success tich-mt-4">{{ session('status') }}</div>
    @endif
    @error('time_log')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    @if ($log->status === 'returned' && $log->hr_notes)
        <div class="tich-alert tich-alert--info tich-mt-4">
            <strong>Returned by HR.</strong> {{ $log->hr_notes }}
        </div>
    @endif

    <p class="tich-caption tich-mt-4">
        Status: <x-status-badge :status="$log->status" />
        @if ($log->manager_signed_at)
            · Locked after line-manager endorsement
        @elseif ($log->status === 'pending_hr' && $isOwner)
            · Submitted to HR — you can still edit until your line manager endorses
        @endif
        @if ($canSelfEndorse && $canSubmit)
            · On submit you will also self-endorse as HOD (locks the form)
        @endif
    </p>

    @if ($editable)
        <form method="POST" action="{{ route('employee.time-logs.update', $log) }}" id="wtl-edit-form">
            @csrf
            @method('PUT')
            @include('partials.weekly-time-log-form', ['log' => $log, 'departments' => $departments, 'editable' => true])
            <div class="tich-flex-wrap tich-mt-4" style="gap:0.75rem;">
                <button type="submit" class="tich-btn tich-btn-secondary">Save draft</button>
            </div>
        </form>
        @if ($canSubmit)
            <form method="POST" action="{{ route('employee.time-logs.submit', $log) }}" id="wtl-submit-form" class="tich-mt-2" onsubmit="return copyDaysAndConfirm(this);">
                @csrf
                <div id="wtl-submit-fields"></div>
                <button type="submit" class="tich-btn tich-btn-primary">Submit to HR</button>
            </form>
            <script>
            function copyDaysAndConfirm(form) {
                if (!confirm('Submit this weekly time log to HR? Your signature and today’s date will be recorded.')) return false;
                var edit = document.getElementById('wtl-edit-form');
                var box = document.getElementById('wtl-submit-fields');
                box.innerHTML = '';
                if (!edit) return true;
                edit.querySelectorAll('input, textarea, select').forEach(function (el) {
                    if (!el.name || el.name === '_token' || el.name === '_method') return;
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (!el.checked) return;
                    }
                    if (el.tagName === 'SELECT' && el.multiple) {
                        Array.from(el.selectedOptions).forEach(function (opt) {
                            var h = document.createElement('input');
                            h.type = 'hidden';
                            h.name = el.name;
                            h.value = opt.value;
                            box.appendChild(h);
                        });
                        return;
                    }
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = el.name;
                    hidden.value = el.value;
                    box.appendChild(hidden);
                });
                return true;
            }
            </script>
        @endif
    @else
        @include('partials.weekly-time-log-form', ['log' => $log, 'departments' => $departments, 'editable' => false])
    @endif

    @if ($canEndorse)
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">Line manager / HOD endorsement</h2>
            <p class="tich-caption tich-mt-1">Digital endorsement locks the form for the employee.</p>
            <form method="POST" action="{{ route('employee.time-logs.endorse', $log) }}" class="tich-form-stack tich-mt-4">
                @csrf
                <div class="tich-form-group">
                    <label class="tich-label" for="signature">Digital signature (full name)</label>
                    <input type="text" id="signature" name="signature" class="tich-input" required maxlength="300" value="{{ $staff->fullName() }}">
                </div>
                <button type="submit" class="tich-btn tich-btn-primary">Endorse &amp; lock</button>
            </form>
        </div>
    @endif

    @if ($log->hr_notes && in_array($log->status, ['approved', 'rejected'], true))
        <div class="tich-card tich-mt-6">
            <h2 class="tich-h3">HR decision</h2>
            <p class="tich-caption">{{ $log->hr_notes }}</p>
            <p class="tich-caption tich-mt-2">{{ $log->hr_reviewed_at?->format('d M Y H:i') }}</p>
        </div>
    @endif
@endsection
