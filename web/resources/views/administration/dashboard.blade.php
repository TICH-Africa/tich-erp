@extends('layouts.administration')

@section('title', 'Administration Dashboard')

@section('administration-content')
    <x-page-toolbar title="Administration" meta="Institutional planning, admissions ops, compliance, and procurement visibility" />

    @if ($department)
        <article class="tich-card tich-mt-8">
            <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <p class="tich-caption">Administrative unit</p>
                    <h2 class="tich-h3 tich-mt-2">{{ $department->dept_name }}</h2>
                    <p class="tich-caption tich-mt-2">{{ $department->dept_code }} · Department operations, workflows, and records.</p>
                </div>
                <span class="tich-badge">Administration module enabled</span>
            </div>
        </article>
    @endif

    <div class="tich-stat-row tich-stat-row--4 tich-mt-8">
        <div class="tich-stat">
            <p class="tich-stat__label">Open planning cycles</p>
            <p class="tich-stat__value">{{ number_format($planningOpen) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Pending approvals</p>
            <p class="tich-stat__value">{{ number_format($pendingApprovals) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Funds released</p>
            <p class="tich-stat__value">KES {{ number_format($releasedFunds, 0) }}</p>
        </div>
        <div class="tich-stat">
            <p class="tich-stat__label">Inspection readiness</p>
            <p class="tich-stat__value">{{ number_format($inspectionScore, 0) }}%</p>
        </div>
    </div>

    <div class="tich-mt-8" style="display:grid;gap:1.5rem;">
        <article class="tich-card">
            <div style="padding: 1.25rem 1.25rem 0.75rem;">
                <h2 class="tich-h3">1. Annual institutional plan</h2>
                <p class="tich-caption tich-mt-2">Record intakes, trimesters, holidays, graduation, and field-placement blocks.</p>
            </div>
            <div style="padding: 0 1.25rem 1.25rem;">
                <form method="POST" action="{{ route('administration.workflow.calendar.store') }}" class="tich-form-grid" style="display:grid;gap:0.75rem;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end;">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Fiscal year</label>
                        <input type="number" name="fiscal_year" class="tich-input" value="{{ date('Y') }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Event type</label>
                        <select name="event_type" class="tich-input" required>
                            <option value="intake">Intake</option>
                            <option value="trimester">Trimester</option>
                            <option value="holiday">Holiday</option>
                            <option value="graduation">Graduation</option>
                            <option value="field_placement">Field placement</option>
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Title</label>
                        <input type="text" name="title" class="tich-input" placeholder="September Intake" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Starts</label>
                        <input type="date" name="starts_on" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Ends</label>
                        <input type="date" name="ends_on" class="tich-input">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Notes</label>
                        <input type="text" name="notes" class="tich-input">
                    </div>
                    <div>
                        <button type="submit" class="tich-btn tich-btn-primary">Save calendar event</button>
                    </div>
                </form>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Event type</th>
                            <th>Title</th>
                            <th>Starts</th>
                            <th>Ends</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($calendarEvents as $event)
                            <tr>
                                <td><span class="tich-badge">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</span></td>
                                <td><strong>{{ $event->title }}</strong></td>
                                <td>{{ $event->starts_on?->format('d/m/Y') }}</td>
                                <td>{{ $event->ends_on?->format('d/m/Y') }}</td>
                                <td>{{ $event->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="tich-table-empty">No calendar events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="tich-card">
            <div style="padding: 1.25rem 1.25rem 0.75rem;">
                <h2 class="tich-h3">2-3. Monthly to weekly task board</h2>
                <p class="tich-caption tich-mt-2">Section Heads add named owners, due dates, milestones, and budget implications.</p>
            </div>
            <div style="padding: 0 1.25rem 1.25rem;">
                <form method="POST" action="{{ route('administration.workflow.tasks.store') }}" class="tich-form-grid" style="display:grid;gap:0.75rem;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end;">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Department</label>
                        <select name="department_id" class="tich-input" required>
                            <option value="">Select department</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Monthly plan</label>
                        <select name="planning_cycle_id" class="tich-input">
                            <option value="">Unlinked</option>
                            @foreach ($planningCycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Task or milestone</label>
                        <input type="text" name="title" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Owner user ID</label>
                        <input type="number" name="owner_id" class="tich-input" min="1">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Due date</label>
                        <input type="date" name="due_on" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Budget implication</label>
                        <input type="number" step="0.01" name="budget_implication" class="tich-input" value="0">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Description</label>
                        <input type="text" name="description" class="tich-input">
                    </div>
                    <div>
                        <button type="submit" class="tich-btn tich-btn-primary">Add weekly task</button>
                    </div>
                </form>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Owner</th>
                            <th>Due date</th>
                            <th>Budget implication</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($adminTasks as $task)
                            <tr>
                                <td><strong>{{ $task->title }}</strong></td>
                                <td>{{ $task->owner_id ?: '—' }}</td>
                                <td>{{ $task->due_on?->format('d/m/Y') }}</td>
                                <td>KES {{ number_format($task->budget_implication, 2) }}</td>
                                <td><span class="tich-badge">{{ ucfirst($task->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="tich-table-empty">No weekly tasks recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="tich-card">
            <div class="tich-table-panel" style="padding: 0; overflow: visible;">
                <div style="padding: 1.25rem 1.25rem 0.75rem;">
                    <h2 class="tich-h3">4. Prior-month deadline control</h2>
                    <p class="tich-caption tich-mt-2">Planning cycles keep their submission deadline. Budget requests submitted after it are marked late with the deadline timestamp for review.</p>
                </div>
                <div class="tich-table-wrap">
                    <table class="tich-admin-table">
                        <thead>
                            <tr>
                                <th>Cycle</th>
                                <th>Tier</th>
                                <th>Deadline</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($planningCycles as $cycle)
                                <tr>
                                    <td><strong>{{ $cycle->title }}</strong></td>
                                    <td>{{ ucfirst($cycle->plan_tier) }}</td>
                                    <td>{{ $cycle->requisition_deadline?->format('d/m/Y H:i') }}</td>
                                    <td><span class="tich-badge">{{ ucfirst($cycle->status) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="tich-table-empty">No planning cycles with deadlines recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </article>

        <article class="tich-card">
            <div style="padding: 1.25rem 1.25rem 0.75rem;">
                <h2 class="tich-h3">5. Monthly variance and lessons</h2>
                <p class="tich-caption tich-mt-2">Director of Administration records planned versus executed amounts and carries lessons into the next plan.</p>
            </div>
            <div style="padding: 0 1.25rem 1.25rem;">
                <form method="POST" action="{{ route('administration.workflow.variances.store') }}" class="tich-form-grid" style="display:grid;gap:0.75rem;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end;">
                    @csrf
                    <div class="tich-form-group">
                        <label class="tich-label">Department</label>
                        <select name="department_id" class="tich-input" required>
                            <option value="">Select department</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->dept_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Year</label>
                        <input type="number" name="fiscal_year" class="tich-input" value="{{ date('Y') }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Month</label>
                        <input type="number" name="month" class="tich-input" min="1" max="12" value="{{ date('n') }}" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Planned amount</label>
                        <input type="number" step="0.01" name="planned_amount" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Actual amount</label>
                        <input type="number" step="0.01" name="actual_amount" class="tich-input" required>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Monthly plan</label>
                        <select name="planning_cycle_id" class="tich-input">
                            <option value="">Unlinked</option>
                            @foreach ($planningCycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Explanation</label>
                        <input type="text" name="explanation" class="tich-input">
                    </div>
                    <div class="tich-form-group">
                        <label class="tich-label">Lessons for next plan</label>
                        <input type="text" name="lessons" class="tich-input">
                    </div>
                    <div>
                        <button type="submit" class="tich-btn tich-btn-primary">Save variance review</button>
                    </div>
                </form>
            </div>
            <div class="tich-table-wrap">
                <table class="tich-admin-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Period</th>
                            <th>Planned</th>
                            <th>Actual</th>
                            <th>Variance</th>
                            <th>Explanation</th>
                            <th>Lessons</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($variances as $variance)
                            <tr>
                                <td><strong>{{ $variance->department?->dept_name ?? '—' }}</strong></td>
                                <td>{{ $variance->fiscal_year }} / {{ str_pad((string) $variance->month, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>KES {{ number_format($variance->planned_amount, 2) }}</td>
                                <td>KES {{ number_format($variance->actual_amount, 2) }}</td>
                                <td>KES {{ number_format($variance->planned_amount - $variance->actual_amount, 2) }}</td>
                                <td>{{ $variance->explanation ?: '—' }}</td>
                                <td>{{ $variance->lessons ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="tich-table-empty">No monthly variance reviews recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </div>

    <h3 class="tich-h3 tich-mt-8 tich-mb-4">Module hubs</h3>
    <div class="tich-grid tich-grid--3" style="gap: 0.75rem;">
        <a href="{{ route('administration.planning.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Multi-tier planning</h3>
            <p class="tich-caption tich-mt-2">Annual, monthly, and weekly cycles with requisition deadlines.</p>
        </a>
        <a href="{{ route('administration.budget-aggregation.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Budget aggregation</h3>
            <p class="tich-caption tich-mt-2">Cross-department consolidation and CBE frameworks.</p>
        </a>
        <a href="{{ route('administration.approvals.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Approval workflow</h3>
            <p class="tich-caption tich-mt-2">Department → Finance → Executive/CEO authorization.</p>
        </a>
        <a href="{{ route('administration.fund-distribution.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Fund distribution</h3>
            <p class="tich-caption tich-mt-2">Digital release of monthly allocations.</p>
        </a>
        <a href="{{ route('administration.statutory.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">Statutory tracking</h3>
            <p class="tich-caption tich-mt-2">KRA, TVETA, and MoE certifications.</p>
        </a>
        <a href="{{ route('administration.ledger-sync.index') }}" class="tich-card tich-card--hover" style="text-decoration:none;color:inherit;">
            <h3 class="tich-h4">QuickBooks sync</h3>
            <p class="tich-caption tich-mt-2">Payment and AP ledger synchronization.</p>
        </a>
    </div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script id="administration-dashboard-chart-data" type="application/json">@json($chartData)</script>
    <script src="{{ asset('js/tich-administration-dashboard.js') }}" defer></script>
@endsection
