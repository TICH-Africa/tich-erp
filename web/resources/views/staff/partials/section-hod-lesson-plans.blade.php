<x-page-toolbar title="HOD Lesson plans" :meta="$staff->department?->dept_name . ' · Management'" />

<div class="tich-mt-6">
    <p class="tich-text tich-mt-2">Submitted and modified lesson plans from tutors in your department.</p>
    @if ($hodManagement['lesson_plans']->isEmpty())
        <p class="tich-text tich-mt-4">No pending lesson plans.</p>
    @else
        <div class="tich-table-wrap tich-mt-4">
            <table class="tich-admin-table">
                <thead><tr><th>Tutor</th><th>Unit</th><th>Date</th><th>Hrs</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($hodManagement['lesson_plans'] as $plan)
                        <tr>
                            <td>{{ $plan->tutor_name }}</td>
                            <td>{{ $plan->unit_code }} - {{ $plan->unit_name }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($plan->planned_date)->format('d M Y') }}</td>
                            <td>{{ $plan->contact_hours }}</td>
                            <td>{{ ucfirst($plan->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="tich-caption tich-mt-2">Open <a href="{{ route('departments.academics.lesson-plans.index', ['learning_department' => $staff->department_id]) }}" class="tich-link">Lesson plan approval</a> in Academics to review and approve/reject.</p>
    @endif
</div>
