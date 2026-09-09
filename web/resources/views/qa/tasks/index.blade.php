@php
    $moduleContext = $moduleContext ?? \App\Support\QaTaskModuleContext::forModule('qa');
    $taskRoutes = $taskRoutes ?? $moduleContext['routes'];
@endphp

@extends($moduleContext['layout'])

@section('title', 'My department QA tasks')

@section($moduleContext['content_section'])
    <x-page-toolbar title="My department QA tasks" meta="Outstanding assessment sheets assigned to your department(s)" />

    <div class="tich-card tich-table-panel tich-mt-8">
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
                @forelse ($tasks as $task)
                    <tr>
                        <td>
                            <strong>{{ $task->plan->plan_name }}</strong>
                            <p class="tich-caption">{{ str_replace('_', ' ', $task->plan->status) }}</p>
                        </td>
                        <td>
                            <a href="{{ \App\Support\QaTaskModuleContext::url('show', $moduleContext['key'], ['plan' => $task->plan, 'department' => $task->department]) }}" class="tich-link">{{ $task->department->dept_name }}</a>
                        </td>
                        <td>{{ $task->plan->due_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <a href="{{ \App\Support\QaTaskModuleContext::url('show', $moduleContext['key'], ['plan' => $task->plan, 'department' => $task->department]) }}" class="tich-btn tich-btn-primary">Fill</a>
                        </td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', ['colspan' => 4, 'title' => 'No outstanding QA tasks', 'icon' => 'inbox'])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
