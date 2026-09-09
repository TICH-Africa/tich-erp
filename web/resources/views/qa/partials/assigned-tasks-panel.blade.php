@php
    $qaPendingTasks = $qaPendingTasks ?? collect();
    $qaModuleKey = \App\Support\QaTaskModuleContext::currentModuleKey();
@endphp

@if ($qaPendingTasks->isNotEmpty())
    <article class="tich-card tich-mb-8" id="qa-assigned-tasks" style="border-left:4px solid var(--tich-blue, #1d4ed8);">
        <div class="tich-flex tich-flex--between" style="flex-wrap:wrap; gap:0.75rem; align-items:flex-start;">
            <div>
                <p class="tich-caption">Quality assurance</p>
                <h2 class="tich-h3 tich-mt-2">Assigned assessment sheets</h2>
                <p class="tich-text tich-mt-2">
                    {{ $qaPendingTasks->count() }} dispatched assessment{{ $qaPendingTasks->count() === 1 ? '' : 's' }}
                    waiting for your department response.
                </p>
            </div>
            <a href="{{ \App\Support\QaTaskModuleContext::url('index', $qaModuleKey) }}" class="tich-btn tich-btn-primary">Open all QA tasks</a>
        </div>

        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Assessment</th>
                        <th>Department</th>
                        <th>Due</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($qaPendingTasks->take(8) as $task)
                        <tr>
                            <td>
                                <strong>{{ $task->plan->plan_name }}</strong>
                                <p class="tich-caption">{{ str_replace('_', ' ', $task->plan->status) }}</p>
                            </td>
                            <td>{{ $task->department->dept_name }}</td>
                            <td>{{ $task->plan->due_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>
                                <a href="{{ \App\Support\QaTaskModuleContext::url('show', $qaModuleKey, ['plan' => $task->plan, 'department' => $task->department]) }}" class="tich-btn tich-btn-secondary">Fill</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
@endif
