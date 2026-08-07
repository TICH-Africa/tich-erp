<x-page-toolbar title="Employee Relations" :meta="$staff->employee_number . ' · ' . ($staff->job_title ?? 'Staff')" />

<section class="tich-mt-6">
    <h2 class="tich-h3 tich-mb-4">My Grievances</h2>
    <p class="tich-text tich-mb-4">
        <a href="{{ route('employee.relations.grievances.index') }}" class="tich-link">View all my grievances</a>
    </p>
</section>

<section class="tich-mt-6">
    <h2 class="tich-h3 tich-mb-4">My Feedback</h2>
    <p class="tich-text tich-mb-4">
        <a href="{{ route('employee.relations.feedback.index') }}" class="tich-link">View all my feedback</a>
    </p>
</section>
