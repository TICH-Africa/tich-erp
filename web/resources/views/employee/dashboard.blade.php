@extends('layouts.employee')

@section('employee-content')
    @php
        $contract = $contractSummary;
        $pay = $compensation;
    @endphp

    <x-page-toolbar
        :title="$staff->fullName()"
        :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Employee') . ' · ' . ($staff->department?->dept_name ?? 'Unassigned department')"
    >
        <x-slot:actions>
            <a href="{{ route('employee.profile.edit') }}" class="tich-btn tich-btn-primary">Update profile</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-employee-profile-header tich-mt-6">
        <div class="tich-employee-profile-header__photo">
            @if ($staff->photoUrl())
                <img src="{{ $staff->photoUrl() }}" alt="{{ $staff->fullName() }}">
            @else
                <span class="tich-employee-profile-header__initials">{{ $staff->initials() }}</span>
            @endif
        </div>
        <div>
            <p class="tich-caption">Employee profile</p>
            <p class="tich-text tich-mt-2">Personal detail changes require HR approval. Use <strong>Update profile</strong> to submit photo, contact, or qualification updates.</p>
            @if (($pendingProfileChanges ?? 0) > 0)
                <p class="tich-caption tich-mt-2" style="color:#b45309;">{{ $pendingProfileChanges }} change request(s) awaiting HR review.</p>
            @endif
        </div>
    </div>

    <style>
        .tich-employee-profile-header { display:flex; gap:1.25rem; align-items:center; flex-wrap:wrap; padding:1.25rem; background:var(--tich-white); border:1px solid var(--tich-neutral-border); border-radius:var(--radius-md); }
        .tich-employee-profile-header__photo { width:5.5rem; height:5.5rem; border-radius:50%; overflow:hidden; flex-shrink:0; border:2px solid var(--tich-neutral-border); background:var(--tich-surface-muted, #f1f5f9); display:flex; align-items:center; justify-content:center; }
        .tich-employee-profile-header__photo img { width:100%; height:100%; object-fit:cover; }
        .tich-employee-profile-header__initials { font-family:var(--font-heading); font-size:1.25rem; font-weight:700; color:var(--tich-blue); }
    </style>

    <article class="tich-card tich-mt-6" style="border-left:4px solid #dc2626;">
        <div class="tich-flex tich-flex--between" style="flex-wrap:wrap; gap:0.75rem; align-items:flex-start;">
            <div>
                <p class="tich-caption">Employee support</p>
                <h2 class="tich-h3 tich-mt-2">Concerns &amp; issues</h2>
                <p class="tich-text tich-mt-2">Report workplace concerns anytime. HR receives and tracks every submission.</p>
            </div>
            <a href="{{ route('employee.concerns.create') }}" class="tich-btn tich-btn-primary">Raise a concern</a>
        </div>
    </article>

    <div class="tich-grid tich-grid--4 tich-dept-stats tich-mt-6">
        <article class="tich-card tich-stat">
            <p class="tich-caption">Employment status</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">{{ ucfirst(str_replace('_', ' ', $staff->employment_status)) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Contract</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">
                @if ($contract['status'] === 'open_ended')
                    Open-ended
                @elseif ($contract['days_remaining'] !== null && $contract['days_remaining'] >= 0)
                    {{ $contract['days_remaining'] }} days left
                @elseif ($contract['end_date'])
                    Expired
                @else
                    Not set
                @endif
            </p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Monthly compensation</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">KES {{ number_format($pay['total_monthly'], 0) }}</p>
        </article>
        <article class="tich-card tich-stat">
            <p class="tich-caption">Time with TICH</p>
            <p class="tich-stat__value" style="font-size:1.1rem;">{{ $employmentDuration ?? '-' }}</p>
        </article>
    </div>

    <nav class="tich-employee-section-nav tich-mt-8" aria-label="Profile sections" data-employee-tabs>
        <button type="button" class="is-active" data-employee-tab="employment">Employment</button>
        <button type="button" data-employee-tab="contact">Contact</button>
        <button type="button" data-employee-tab="leave">Leave</button>
        <button type="button" data-employee-tab="training">Training</button>
        <button type="button" data-employee-tab="records">Records</button>
    </nav>

    <div class="tich-employee-panel" data-employee-panel="employment">
        <div class="tich-grid tich-grid--2 tich-mt-4" style="align-items:start; gap:1.5rem;">
            <article class="tich-card">
                <h2 class="tich-h3">Employment &amp; contract</h2>
                <div class="tich-kv-grid tich-mt-4">
                    <div><span class="tich-kv-grid__label">Department</span><span class="tich-kv-grid__value">{{ $staff->department?->dept_name ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Campus</span><span class="tich-kv-grid__value">{{ $staff->campus?->campus_name ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Job title</span><span class="tich-kv-grid__value">{{ $staff->job_title ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Line manager</span><span class="tich-kv-grid__value">{{ $staff->lineManager?->fullName() ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Start date</span><span class="tich-kv-grid__value">{{ $staff->employment_start_date?->format('d M Y') ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Contract end</span><span class="tich-kv-grid__value">{{ $contract['end_date']?->format('d M Y') ?? 'Open-ended / permanent' }}</span></div>
                    @if ($currentContract)
                        <div><span class="tich-kv-grid__label">Contract no.</span><span class="tich-kv-grid__value">{{ $currentContract->contract_number }}</span></div>
                        <div><span class="tich-kv-grid__label">Contract type</span><span class="tich-kv-grid__value">{{ ucfirst(str_replace('_', ' ', $currentContract->contract_type)) }}</span></div>
                    @endif
                </div>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Compensation</h2>
                <div class="tich-kv-grid tich-mt-4">
                    <div><span class="tich-kv-grid__label">Consolidated Gross Pay</span><span class="tich-kv-grid__value">KES {{ number_format($pay['gross_monthly_salary'], 2) }}</span></div>
                    <div><span class="tich-kv-grid__label">Allowances</span><span class="tich-kv-grid__value">KES {{ number_format($pay['allowances_total'], 2) }}</span></div>
                    <div><span class="tich-kv-grid__label">Total package</span><span class="tich-kv-grid__value">KES {{ number_format($pay['total_monthly'], 2) }}</span></div>
                </div>
                @if ($pay['allowances']->isNotEmpty())
                    <h3 class="tich-h3 tich-mt-6">Allowance breakdown</h3>
                    <ul class="tich-mt-2" style="list-style:none; padding:0;">
                        @foreach ($pay['allowances'] as $allowance)
                            <li class="tich-text tich-mt-2" style="display:flex; justify-content:space-between; gap:1rem; border-bottom:1px solid var(--tich-neutral-border); padding-bottom:0.5rem;">
                                <span>{{ $allowance->allowance_name }}</span>
                                <strong>KES {{ number_format($allowance->amount, 2) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>

        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Contract history</h2>
            @forelse ($staff->contracts as $contractItem)
                <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                    <strong>{{ $contractItem->contract_number }}</strong>
                    <span class="tich-caption"> · {{ ucfirst(str_replace('_', ' ', $contractItem->contract_type)) }}</span>
                    <p class="tich-caption tich-mt-2">
                        {{ $contractItem->start_date?->format('d M Y') }} → {{ $contractItem->end_date?->format('d M Y') ?? 'Ongoing' }}
                        @if ($contractItem->isExpired())
                            · Expired
                        @elseif ($contractItem->isExpiringSoon())
                            · Expiring soon
                        @endif
                    </p>
                </div>
            @empty
                <p class="tich-text tich-mt-4">No contracts on file.</p>
            @endforelse
        </article>
    </div>

    <div class="tich-employee-panel" data-employee-panel="contact" hidden>
        <div class="tich-grid tich-grid--2 tich-mt-4" style="align-items:start; gap:1.5rem;">
            <article class="tich-card">
                <h2 class="tich-h3">Personal &amp; contact</h2>
                <div class="tich-kv-grid tich-mt-4">
                    <div><span class="tich-kv-grid__label">Marital status</span><span class="tich-kv-grid__value">{{ $staff->marital_status ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Organisation email</span><span class="tich-kv-grid__value">{{ $staff->organisation_email ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Personal email</span><span class="tich-kv-grid__value">{{ $staff->primary_email ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Phone</span><span class="tich-kv-grid__value">{{ $staff->phone_number ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Alt. phone</span><span class="tich-kv-grid__value">{{ $staff->alt_phone_number ?? '-' }}</span></div>
                    <div style="grid-column:1/-1;">
                        <span class="tich-kv-grid__label">Emergency contact</span>
                        <span class="tich-kv-grid__value">
                            @if ($staff->emergency_contact_name)
                                {{ $staff->emergency_contact_name }}
                                @if ($staff->emergency_contact_relationship) ({{ $staff->emergency_contact_relationship }}) @endif
                                @if ($staff->emergency_contact_phone) · {{ $staff->emergency_contact_phone }} @endif
                            @else - @endif
                        </span>
                    </div>
                    <div style="grid-column:1/-1;">
                        <span class="tich-kv-grid__label">Physical address</span>
                        <p class="tich-kv-grid__value tich-kv-grid__value--block tich-mt-2">{{ $staff->physical_address ?? '-' }}</p>
                    </div>
                </div>
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Statutory &amp; payroll details</h2>
                <div class="tich-kv-grid tich-mt-4">
                    <div><span class="tich-kv-grid__label">KRA PIN</span><span class="tich-kv-grid__value">{{ $staff->kra_pin ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">NSSF number</span><span class="tich-kv-grid__value">{{ $staff->nssf_number ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">SHA number</span><span class="tich-kv-grid__value">{{ $staff->sha_number ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Bank</span><span class="tich-kv-grid__value">{{ $staff->bankAccount?->bank_name ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Account name</span><span class="tich-kv-grid__value">{{ $staff->bankAccount?->account_name ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Account number</span><span class="tich-kv-grid__value">{{ $pay['masked_account_number'] ?? '-' }}</span></div>
                    <div><span class="tich-kv-grid__label">Pension scheme</span><span class="tich-kv-grid__value">{{ $staff->pensionScheme?->scheme_name ?? '-' }}</span></div>
                </div>
            </article>
        </div>

        <article class="tich-card tich-mt-8">
            <h2 class="tich-h3">Next of kin</h2>
            @forelse ($staff->nextOfKin as $kin)
                <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                    <strong>{{ $kin->full_name }}</strong>
                    <span class="tich-caption"> · {{ $kin->relationship }}</span>
                    <p class="tich-caption tich-mt-2">{{ $kin->phone_number }}@if($kin->email) · {{ $kin->email }}@endif</p>
                </div>
            @empty
                <p class="tich-text tich-mt-4">No next of kin on file.</p>
            @endforelse
        </article>
    </div>

    <div class="tich-employee-panel" data-employee-panel="leave" hidden>
        <article class="tich-card tich-mt-4">
            <div class="tich-flex tich-flex--between" style="flex-wrap:wrap; gap:0.75rem;">
                <h2 class="tich-h3">Leave balance ({{ now()->year }})</h2>
                <a href="{{ route('employee.leave.index') }}" class="tich-btn tich-btn-primary">Apply for leave</a>
            </div>
            @if ($leaveBalances->isEmpty())
                <p class="tich-text tich-mt-4">No leave balances recorded for this year.</p>
            @else
                <div class="tich-leave-balance-grid tich-mt-4">
                    @foreach ($leaveBalances as $balance)
                        @php
                            $entitled = max((int) $balance->entitled_days, 1);
                            $usedPct = min(100, ((int) $balance->days_taken + (int) $balance->days_pending) / $entitled * 100);
                        @endphp
                        <article class="tich-leave-balance-card">
                            <div class="tich-leave-balance-card__head">
                                <span class="tich-leave-balance-card__name">{{ $balance->leave_type_name }}</span>
                                <span class="tich-leave-balance-card__remaining">{{ (int) $balance->balance_days }} left</span>
                            </div>
                            <div class="tich-leave-balance-card__meter" aria-hidden="true">
                                <span class="tich-leave-balance-card__meter-fill" style="width: {{ $usedPct }}%;"></span>
                            </div>
                            <div class="tich-leave-balance-card__meta">
                                <span>{{ (int) $balance->days_taken }} taken</span>
                                <span>{{ (int) $balance->days_pending }} pending</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="tich-card tich-mt-8">
            <div class="tich-flex tich-flex--between">
                <h2 class="tich-h3">Recent leave requests</h2>
                <a href="{{ route('employee.leave.index') }}" class="tich-btn tich-btn-ghost">View all</a>
            </div>
            @forelse ($recentLeaveRequests as $leave)
                <div class="tich-leave-request-item tich-mt-4" style="margin-top:1rem;">
                    <div>
                        <div class="tich-leave-request-item__title">
                            <strong>{{ $leave->leave_type_name }}</strong>
                            @include('partials.leave-status-badge', ['status' => $leave->overall_status])
                        </div>
                        <p class="tich-leave-request-item__meta">
                            {{ \Illuminate\Support\Carbon::parse($leave->start_date)->format('d M Y') }} → {{ \Illuminate\Support\Carbon::parse($leave->end_date)->format('d M Y') }}
                            · {{ (int) $leave->days_requested }} day(s)
                        </p>
                    </div>
                </div>
            @empty
                <p class="tich-text tich-mt-4">No leave requests on file.</p>
            @endforelse
        </article>
    </div>

    <div class="tich-employee-panel" data-employee-panel="training" hidden>
        <article class="tich-card">
            <h2 class="tich-h3">Assigned Training</h2>
            @if ($trainings->isEmpty())
                <p class="tich-text tich-mt-4">No active training assignments.</p>
            @else
                <div class="tich-mt-4">
                    @foreach ($trainings as $training)
                        <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                            <strong>{{ $training->activity_name }}</strong>
                            <span class="tich-badge tich-badge--info tich-ml-2">{{ ucfirst($training->activity_type) }}</span>
                            <p class="tich-caption tich-mt-2">
                                {{ $training->organizer ?? 'Internal' }} · {{ $training->start_date?->format('d M Y') }}
                                @if ($training->end_date) → {{ $training->end_date->format('d M Y') }}@endif
                            </p>
                            <p class="tich-caption">
                                Hours: {{ $training->hours_or_days ?? 0 }} · CPD Credits: {{ $training->cpd_credits_earned ?? 0 }}
                                @if ($training->location) · {{ $training->location }}@endif
                            </p>
                            <span class="tich-badge tich-badge--{{ $training->is_completed ? 'success' : 'warning' }} tich-mt-2">
                                {{ $training->is_completed ? 'Completed' : 'In Progress' }}
                            </span>
                            @if ($training->is_completed)
                                <a href="{{ route('staff.documents.create') }}" class="tich-btn tich-btn-primary tich-ml-2" style="font-size:0.75rem; padding:0.35rem 0.75rem;">
                                    Submit Certification
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </div>

    <div class="tich-employee-panel" data-employee-panel="records" hidden>
        <div class="tich-grid tich-grid--2 tich-mt-4" style="align-items:start; gap:1.5rem;">
            <article class="tich-card">
                <h2 class="tich-h3">Qualifications</h2>
                @forelse ($staff->qualifications as $qualification)
                    <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                        <strong>{{ $qualification->qualification_name ?: ucfirst(str_replace('_', ' ', $qualification->qualification_type)) }}</strong>
                        <p class="tich-caption tich-mt-2">{{ $qualification->institution ?? '-' }} · {{ $qualification->year_completed ?? '-' }}@if($qualification->grade_or_class) · {{ $qualification->grade_or_class }}@endif</p>
                    </div>
                @empty
                    <p class="tich-text tich-mt-4">No qualifications recorded.</p>
                @endforelse
            </article>

            <article class="tich-card">
                <h2 class="tich-h3">Documents &amp; licenses</h2>
                @forelse ($staff->documents as $document)
                    <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                        <strong>{{ $document->document_name }}</strong>
                        <span class="tich-caption"> · {{ $document->is_verified ? 'Verified' : 'Pending verification' }}</span>
                        <p class="tich-caption tich-mt-2">{{ ucfirst($document->document_type) }} · Expires {{ $document->expiry_date?->format('d M Y') ?? '-' }}</p>
                    </div>
                @empty
                    <p class="tich-text tich-mt-4">No HR documents on file.</p>
                @endforelse

                @if ($staff->professionalLicenses->isNotEmpty())
                    <h3 class="tich-h3 tich-mt-6">Professional licenses</h3>
                    @foreach ($staff->professionalLicenses as $license)
                        <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                            <strong>{{ ucfirst(str_replace('_', ' ', $license->license_type)) }}</strong>
                            <p class="tich-caption tich-mt-2">
                                {{ $license->issuing_body ?? '-' }} · {{ $license->license_number ?? '-' }}
                                · Expires {{ $license->expiry_date?->format('d M Y') ?? '-' }}
                            </p>
                        </div>
                    @endforeach
                @endif
            </article>
        </div>

        @if ($staff->performanceReviews->isNotEmpty())
            <article class="tich-card tich-mt-8">
                <h2 class="tich-h3">Recent performance reviews</h2>
                @foreach ($staff->performanceReviews as $review)
                    <div class="tich-mt-4" style="padding-bottom:0.75rem; border-bottom:1px solid var(--tich-neutral-border);">
                        <strong>{{ $review->review_period_start?->format('d M Y') ?? 'Review' }}@if($review->review_period_end) - {{ $review->review_period_end->format('d M Y') }}@endif</strong>
                        <span class="tich-caption"> · {{ ucfirst(str_replace('_', ' ', $review->overall_rating ?? 'completed')) }}</span>
                        <p class="tich-caption tich-mt-2">{{ $review->review_date?->format('d M Y') ?? '-' }}</p>
                    </div>
                @endforeach
            </article>
        @endif
    </div>

    <p class="tich-caption tich-mt-8">Payroll and employment records are managed by HR. Use <a href="{{ route('employee.profile.edit') }}">Update profile</a> to request changes to contact details, photo, or qualifications.</p>

    <script>
        (function () {
            var nav = document.querySelector('[data-employee-tabs]');
            if (!nav) {
                return;
            }

            var tabs = nav.querySelectorAll('[data-employee-tab]');
            var panels = document.querySelectorAll('[data-employee-panel]');

            function showPanel(name) {
                tabs.forEach(function (tab) {
                    var active = tab.getAttribute('data-employee-tab') === name;
                    tab.classList.toggle('is-active', active);
                });

                panels.forEach(function (panel) {
                    var show = panel.getAttribute('data-employee-panel') === name;
                    panel.hidden = !show;
                });
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    showPanel(tab.getAttribute('data-employee-tab'));
                });
            });
        })();
    </script>
@endsection
