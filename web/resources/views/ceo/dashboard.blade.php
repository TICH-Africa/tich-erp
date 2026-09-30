@extends('layouts.ceo')

@section('title', 'CEO Office')

@section('ceo-content')
@php
    $workforce = $workforce ?? [];
    $academics = $academics ?? [];
    $admissions = $admissions ?? [];
    $finance = $finance ?? [];
    $queues = $queues ?? [];
    $procurement = $procurement ?? [];

@endphp

<div class="ceo-dash" data-ceo-dash>
    <header class="ceo-dash__hero">
        <div>
            <p class="ceo-dash__eyebrow">Executive office</p>
            <h1 class="ceo-dash__title">Institution dashboard</h1>
            <p class="ceo-dash__lede">Open a domain below to inspect workforce, students, or finance.</p>
        </div>
    </header>

    <nav class="ceo-dash__tabs" role="tablist" aria-label="Dashboard domains">
        <button type="button" class="ceo-dash__tab is-active" role="tab" aria-selected="true" data-ceo-tab="overview">Overview</button>
        <button type="button" class="ceo-dash__tab" role="tab" aria-selected="false" data-ceo-tab="workforce">Workforce</button>
        <button type="button" class="ceo-dash__tab" role="tab" aria-selected="false" data-ceo-tab="students">Students</button>
        <button type="button" class="ceo-dash__tab" role="tab" aria-selected="false" data-ceo-tab="finance">Finance</button>
    </nav>

    {{-- Overview --}}
    <section class="ceo-dash__panel is-active" role="tabpanel" data-ceo-panel="overview">
        <div class="ceo-dash__kpis">
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Active staff</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($workforce['active_staff'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($workforce['total_staff'] ?? 0)) }} total headcount</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Active students</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($academics['active_students'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($academics['total_students'] ?? 0)) }} on record</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Net income</p>
                <p class="ceo-dash__kpi-value ceo-dash__kpi-value--money">KES {{ number_format((float) ($finance['net_income'] ?? 0), 0) }}</p>
                <p class="ceo-dash__kpi-meta">Revenue {{ number_format((float) ($finance['revenue'] ?? 0), 0) }}</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Open vacancies</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($workforce['open_vacancies'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($workforce['available_slots'] ?? 0)) }} slots open</p>
            </article>
        </div>

        <div class="ceo-dash__actions">
            <button type="button" class="tich-btn tich-btn-secondary" data-ceo-goto="workforce">Explore workforce</button>
            <button type="button" class="tich-btn tich-btn-secondary" data-ceo-goto="students">Explore students</button>
            <button type="button" class="tich-btn tich-btn-secondary" data-ceo-goto="finance">Explore finance</button>
            <a href="{{ route('ceo.academics.index') }}" class="tich-btn tich-btn-ghost">Academics hub</a>
            <a href="{{ route('ceo.quality.index') }}" class="tich-btn tich-btn-ghost">Quality reports</a>
        </div>
    </section>

    {{-- Workforce --}}
    <section class="ceo-dash__panel" role="tabpanel" hidden data-ceo-panel="workforce">
        <div class="ceo-dash__kpis">
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Active staff</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($workforce['active_staff'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($workforce['onboarding'] ?? 0)) }} onboarding</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">On leave today</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($workforce['currently_on_leave'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($workforce['pending_leave'] ?? 0)) }} awaiting HR</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Open vacancies</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($workforce['open_vacancies'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($workforce['available_slots'] ?? 0)) }} available slots</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Recruitment pipeline</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($workforce['recruitment_in_pipeline'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($workforce['recruitment_applications'] ?? 0)) }} applications</p>
            </article>
        </div>

        <div class="ceo-dash__panel-toolbar">
            <button type="button" class="tich-btn tich-btn-primary" data-ceo-toggle="workforce-detail" data-label-open="View workforce details" data-label-close="Hide workforce details" aria-expanded="false">
                View workforce details
            </button>
            <span class="ceo-dash__hint">Departments, vacancies, leave, and performance</span>
        </div>

        <div class="ceo-dash__detail" id="workforce-detail" hidden>
            <div class="ceo-dash__detail-grid">
                <div class="ceo-dash__detail-card">
                    <h3 class="ceo-dash__detail-title">Teaching mix</h3>
                    <dl class="ceo-dash__stat-list">
                        <div><dt>Teaching</dt><dd>{{ number_format((int) ($workforce['teaching_staff'] ?? 0)) }}</dd></div>
                        <div><dt>Non-teaching</dt><dd>{{ number_format((int) ($workforce['non_teaching_staff'] ?? 0)) }}</dd></div>
                        <div><dt>Performance reviews</dt><dd>{{ number_format((int) ($workforce['performance_total'] ?? 0)) }}</dd></div>
                        <div><dt>Reviews awaiting HR</dt><dd>{{ number_format((int) ($workforce['performance_awaiting_hr'] ?? 0)) }}</dd></div>
                    </dl>
                </div>

                @if (($workforce['by_department'] ?? collect())->isNotEmpty())
                    <div class="ceo-dash__detail-card">
                        <h3 class="ceo-dash__detail-title">Staff by department</h3>
                        <div class="tich-table-wrap">
                            <table class="tich-admin-table">
                                <thead><tr><th>Department</th><th>Active</th></tr></thead>
                                <tbody>
                                    @foreach ($workforce['by_department'] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td>{{ number_format((int) $row['count']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if (($workforce['open_vacancy_list'] ?? collect())->isNotEmpty())
                    <div class="ceo-dash__detail-card ceo-dash__detail-card--wide">
                        <h3 class="ceo-dash__detail-title">Open vacancies</h3>
                        <div class="tich-table-wrap">
                            <table class="tich-admin-table">
                                <thead><tr><th>Role</th><th>Department</th><th>Slots</th><th>Closes</th></tr></thead>
                                <tbody>
                                    @foreach ($workforce['open_vacancy_list'] as $row)
                                        <tr>
                                            <td>{{ $row['title'] }}</td>
                                            <td>{{ $row['department'] }}</td>
                                            <td>{{ (int) $row['slots_open'] }} / {{ (int) $row['slots_available'] }}</td>
                                            <td>{{ $row['closing_date'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="ceo-dash__detail-card ceo-dash__detail-card--wide">
                    <h3 class="ceo-dash__detail-title">Currently on leave</h3>
                    @if (($workforce['on_leave_sample'] ?? collect())->isNotEmpty())
                        <div class="tich-table-wrap">
                            <table class="tich-admin-table">
                                <thead><tr><th>Staff</th><th>Type</th><th>Period</th></tr></thead>
                                <tbody>
                                    @foreach ($workforce['on_leave_sample'] as $row)
                                        <tr>
                                            <td>
                                                {{ $row['name'] }}
                                                <p class="tich-caption">{{ $row['department'] }}</p>
                                            </td>
                                            <td>{{ $row['leave_type'] }}</td>
                                            <td>{{ $row['period'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="ceo-dash__empty">No staff are on approved leave today.</p>
                    @endif
                </div>

                @if (($workforce['performance_by_rating'] ?? collect())->isNotEmpty())
                    <div class="ceo-dash__detail-card">
                        <h3 class="ceo-dash__detail-title">Performance ratings</h3>
                        <div class="tich-table-wrap">
                            <table class="tich-admin-table">
                                <thead><tr><th>Rating</th><th>Reviews</th></tr></thead>
                                <tbody>
                                    @foreach ($workforce['performance_by_rating'] as $row)
                                        <tr>
                                            <td style="text-transform:capitalize;">{{ $row['rating'] }}</td>
                                            <td>{{ number_format((int) $row['count']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Students --}}
    <section class="ceo-dash__panel" role="tabpanel" hidden data-ceo-panel="students">
        <div class="ceo-dash__kpis">
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Active students</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($academics['active_students'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($academics['programs'] ?? 0)) }} programmes</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Enrolled this year</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($academics['enrolled_this_year'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($academics['enrolled_this_month'] ?? 0)) }} this month</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Admitted</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($admissions['admitted'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($admissions['total_applications'] ?? 0)) }} applications</p>
            </article>
            <article class="ceo-dash__kpi {{ ((int) ($admissions['pending_academic_review'] ?? 0)) > 0 ? 'ceo-dash__kpi--alert' : '' }}">
                <p class="ceo-dash__kpi-label">In academic review</p>
                <p class="ceo-dash__kpi-value">{{ number_format((int) ($admissions['pending_academic_review'] ?? 0)) }}</p>
                <p class="ceo-dash__kpi-meta">{{ number_format((int) ($admissions['fee_pending'] ?? 0)) }} fee pending</p>
            </article>
        </div>

        <div class="ceo-dash__panel-toolbar">
            <button type="button" class="tich-btn tich-btn-primary" data-ceo-toggle="students-detail" data-label-open="View student & admissions details" data-label-close="Hide student & admissions details" aria-expanded="false">
                View student &amp; admissions details
            </button>
            <a href="{{ route('ceo.academics.index') }}" class="tich-btn tich-btn-secondary">Academics hub</a>
            <a href="{{ route('ceo.curriculum.index') }}" class="tich-btn tich-btn-ghost">Curriculum queue</a>
        </div>

        <div class="ceo-dash__detail" id="students-detail" hidden>
            <div class="ceo-dash__detail-grid">
                <div class="ceo-dash__detail-card">
                    <h3 class="ceo-dash__detail-title">Enrollment status</h3>
                    <dl class="ceo-dash__stat-list">
                        <div><dt>Graduated / alumni</dt><dd>{{ number_format((int) ($academics['graduated_students'] ?? 0)) }}</dd></div>
                        <div><dt>Deferred</dt><dd>{{ number_format((int) ($academics['deferred_students'] ?? 0)) }}</dd></div>
                        <div><dt>Suspended</dt><dd>{{ number_format((int) ($academics['suspended_students'] ?? 0)) }}</dd></div>
                        <div><dt>Withdrawn</dt><dd>{{ number_format((int) ($academics['withdrawn_students'] ?? 0)) }}</dd></div>
                    </dl>
                </div>

                <div class="ceo-dash__detail-card">
                    <h3 class="ceo-dash__detail-title">Admissions pipeline</h3>
                    <dl class="ceo-dash__stat-list">
                        <div><dt>New submissions</dt><dd>{{ number_format((int) ($admissions['new_submissions'] ?? 0)) }}</dd></div>
                        <div><dt>Academic review</dt><dd>{{ number_format((int) ($admissions['pending_academic_review'] ?? 0)) }}</dd></div>
                        <div><dt>Fee pending</dt><dd>{{ number_format((int) ($admissions['fee_pending'] ?? 0)) }}</dd></div>
                        <div><dt>Rejected</dt><dd>{{ number_format((int) ($admissions['rejected'] ?? 0)) }}</dd></div>
                    </dl>
                </div>

                @if (($academics['students_by_program'] ?? collect())->isNotEmpty())
                    <div class="ceo-dash__detail-card ceo-dash__detail-card--wide">
                        <h3 class="ceo-dash__detail-title">Students by programme</h3>
                        <div class="tich-table-wrap">
                            <table class="tich-admin-table">
                                <thead><tr><th>Programme</th><th>Students</th></tr></thead>
                                <tbody>
                                    @foreach ($academics['students_by_program'] as $row)
                                        <tr>
                                            <td>{{ $row['name'] }}</td>
                                            <td>{{ number_format((int) $row['count']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Finance --}}
    <section class="ceo-dash__panel" role="tabpanel" hidden data-ceo-panel="finance">
        <div class="ceo-dash__kpis">
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Revenue</p>
                <p class="ceo-dash__kpi-value ceo-dash__kpi-value--money">KES {{ number_format((float) ($finance['revenue'] ?? 0), 0) }}</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Expenditure</p>
                <p class="ceo-dash__kpi-value ceo-dash__kpi-value--money">KES {{ number_format((float) ($finance['expenses'] ?? 0), 0) }}</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Net income</p>
                <p class="ceo-dash__kpi-value ceo-dash__kpi-value--money">KES {{ number_format((float) ($finance['net_income'] ?? 0), 0) }}</p>
            </article>
            <article class="ceo-dash__kpi">
                <p class="ceo-dash__kpi-label">Receivables</p>
                <p class="ceo-dash__kpi-value ceo-dash__kpi-value--money">KES {{ number_format((float) ($finance['accounts_receivable'] ?? 0), 0) }}</p>
            </article>
        </div>

        <div class="ceo-dash__panel-toolbar">
            <button type="button" class="tich-btn tich-btn-primary" data-ceo-toggle="finance-detail" aria-expanded="false">
                View finance details
            </button>
            <a href="{{ route('ceo.budgets.index') }}" class="tich-btn tich-btn-secondary">Budget authorizations</a>
            <a href="{{ route('ceo.procurement.index') }}" class="tich-btn tich-btn-ghost">Procurement queue</a>
            <a href="{{ route('ceo.finance-policy.index') }}" class="tich-btn tich-btn-ghost">Financial policy</a>
        </div>

        <div class="ceo-dash__detail" id="finance-detail" hidden>
            <div class="ceo-dash__detail-grid">
                <div class="ceo-dash__detail-card">
                    <h3 class="ceo-dash__detail-title">Cash &amp; collections</h3>
                    <dl class="ceo-dash__stat-list">
                        <div><dt>Treasury</dt><dd>KES {{ number_format((float) ($finance['treasury_balance'] ?? 0), 0) }}</dd></div>
                        <div><dt>Collected today</dt><dd>KES {{ number_format((float) ($finance['collected_today'] ?? 0), 0) }}</dd></div>
                        <div><dt>Open invoices</dt><dd>{{ number_format((int) ($finance['open_invoices'] ?? 0)) }}</dd></div>
                        <div><dt>Overdue invoices</dt><dd>{{ number_format((int) ($finance['overdue_invoices'] ?? 0)) }}</dd></div>
                    </dl>
                </div>
                <div class="ceo-dash__detail-card">
                    <h3 class="ceo-dash__detail-title">Executive finance queues</h3>
                    <dl class="ceo-dash__stat-list">
                        <div><dt>Budgets awaiting</dt><dd>{{ number_format((int) ($pendingBudgets ?? 0)) }}</dd></div>
                        <div><dt>Procurement awaiting</dt><dd>{{ number_format((int) ($pendingProcurement ?? 0)) }}</dd></div>
                        <div><dt>Procurement in pipeline</dt><dd>{{ number_format((int) ($procurement['in_pipeline'] ?? 0)) }}</dd></div>
                        <div><dt>Policy sign-off</dt><dd>{{ number_format((int) (($queues['finance_policy'] ?? 0) + ($queues['me_policy'] ?? 0))) }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
.ceo-dash { display: grid; gap: 1.25rem; }
.ceo-dash__hero {
    display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem;
    align-items: end; padding: 1.25rem 1.4rem;
    border: 1px solid color-mix(in srgb, var(--tich-border, #d7dde6) 90%, transparent);
    border-radius: 14px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--tich-surface, #fff) 92%, #e8eef6), var(--tich-surface, #fff));
}
.ceo-dash__eyebrow { margin: 0 0 .35rem; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; opacity: .7; }
.ceo-dash__title { margin: 0; font-size: clamp(1.45rem, 2vw, 1.85rem); line-height: 1.2; }
.ceo-dash__lede { margin: .45rem 0 0; max-width: 40rem; opacity: .78; }
.ceo-dash__hint { margin: 0; font-size: .85rem; opacity: .7; }

.ceo-dash__tabs {
    display: flex; flex-wrap: wrap; gap: .4rem;
    padding: .3rem; border-radius: 12px;
    background: color-mix(in srgb, var(--tich-muted-bg, #f1f5f9) 90%, transparent);
    border: 1px solid var(--tich-border, #d7dde6);
}
.ceo-dash__tab {
    border: 0; background: transparent; cursor: pointer;
    padding: .65rem 1rem; border-radius: 9px; font: inherit; color: inherit; opacity: .72;
}
.ceo-dash__tab.is-active {
    background: var(--tich-surface, #fff); opacity: 1; font-weight: 600;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .08);
}

.ceo-dash__panel { display: none; gap: 1rem; }
.ceo-dash__panel.is-active { display: grid; }

.ceo-dash__kpis {
    display: grid; gap: .85rem;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}
.ceo-dash__kpi {
    padding: 1rem 1.05rem; border-radius: 14px;
    border: 1px solid var(--tich-border, #d7dde6);
    background: var(--tich-surface, #fff);
}
.ceo-dash__kpi--alert { border-color: color-mix(in srgb, #b91c1c 35%, var(--tich-border, #d7dde6)); }
.ceo-dash__kpi--ok { border-color: color-mix(in srgb, #15803d 30%, var(--tich-border, #d7dde6)); }
.ceo-dash__kpi-label { margin: 0; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; opacity: .68; }
.ceo-dash__kpi-value { margin: .4rem 0 0; font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
.ceo-dash__kpi-value--money { font-size: 1.25rem; }
.ceo-dash__kpi-meta { margin: .4rem 0 0; font-size: .85rem; opacity: .72; }

.ceo-dash__actions, .ceo-dash__panel-toolbar {
    display: flex; flex-wrap: wrap; gap: .55rem; align-items: center;
}

.ceo-dash__detail {
    padding: 1rem; border-radius: 14px;
    border: 1px solid var(--tich-border, #d7dde6);
    background: color-mix(in srgb, var(--tich-muted-bg, #f8fafc) 80%, var(--tich-surface, #fff));
}
.ceo-dash__detail-grid {
    display: grid; gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}
.ceo-dash__detail-card {
    padding: .9rem 1rem; border-radius: 12px;
    background: var(--tich-surface, #fff);
    border: 1px solid var(--tich-border, #d7dde6);
}
.ceo-dash__detail-card--wide { grid-column: 1 / -1; }
.ceo-dash__detail-title { margin: 0 0 .75rem; font-size: .95rem; }
.ceo-dash__stat-list { margin: 0; display: grid; gap: .55rem; }
.ceo-dash__stat-list > div { display: flex; justify-content: space-between; gap: 1rem; }
.ceo-dash__stat-list dt { opacity: .72; }
.ceo-dash__stat-list dd { margin: 0; font-weight: 600; }
.ceo-dash__empty { margin: 0; opacity: .75; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-ceo-dash]');
    if (!root) return;

    const tabs = [...root.querySelectorAll('[data-ceo-tab]')];
    const panels = [...root.querySelectorAll('[data-ceo-panel]')];

    const activate = (key) => {
        tabs.forEach((tab) => {
            const on = tab.dataset.ceoTab === key;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            const on = panel.dataset.ceoPanel === key;
            panel.classList.toggle('is-active', on);
            panel.hidden = !on;
        });
        if (key && key !== 'overview') {
            history.replaceState(null, '', '#' + key);
        } else if (window.location.hash) {
            history.replaceState(null, '', window.location.pathname + window.location.search);
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activate(tab.dataset.ceoTab));
    });

    root.querySelectorAll('[data-ceo-goto]').forEach((btn) => {
        btn.addEventListener('click', () => activate(btn.dataset.ceoGoto));
    });

    const initialHash = (window.location.hash || '').replace('#', '');
    if (initialHash && root.querySelector('[data-ceo-panel="' + initialHash + '"]')) {
        activate(initialHash);
    }
    root.querySelectorAll('[data-ceo-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.ceoToggle);
            if (!target) return;
            const open = target.hasAttribute('hidden');
            if (open) {
                target.removeAttribute('hidden');
                btn.setAttribute('aria-expanded', 'true');
                btn.textContent = btn.textContent.replace(/^View/, 'Hide');
            } else {
                target.setAttribute('hidden', '');
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = btn.textContent.replace(/^Hide/, 'View');
            }
        });
    });
})();
</script>
@endpush
@endsection
