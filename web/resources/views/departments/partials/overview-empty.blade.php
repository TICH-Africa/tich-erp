<x-page-toolbar title="{{ $department->dept_name }}" meta="{{ $department->dept_code }}" />

<article class="tich-card tich-dept-empty">
    <h2 class="tich-h3">No tools available yet</h2>
    <p class="tich-text tich-mt-2">There are no modules assigned for this department, or you do not have permission to access them.</p>
    <a href="{{ route('dashboard') }}" class="tich-btn tich-btn-secondary tich-mt-4">Back to main dashboard</a>
</article>
