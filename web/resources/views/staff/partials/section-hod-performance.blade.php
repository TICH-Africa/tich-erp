<x-page-toolbar title="Department performance" :meta="$staff->department?->dept_name . ' · Management'" />

<div class="tich-mt-6">
    <p class="tich-text tich-mt-2">Department performance snapshot from existing academics reports.</p>
    @if (empty($hodManagement['performance']))
        <p class="tich-text tich-mt-4">No performance data available.</p>
    @else
        <div class="tich-mt-4">
            <p class="tich-text">Class average: <strong>{{ $hodManagement['performance']['summary']['avg_score'] ?? 0 }}%</strong></p>
            <p class="tich-text">Registered students: <strong>{{ $hodManagement['performance']['summary']['registered_students'] ?? 0 }}</strong></p>
            <p class="tich-text">Failing rate: <strong>{{ $hodManagement['performance']['summary']['failing_rate'] ?? 0 }}%</strong></p>
        </div>
        <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.performance.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Performance terminal</a> in Academics for full analytics.</p>
    @endif
</div>
