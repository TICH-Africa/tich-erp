<header class="tich-dept-header">
    <p class="tich-caption">{{ $overviewStats['category'] }}</p>
    <h1 class="tich-h1 tich-dept-header__title">{{ $department->dept_name }}</h1>
    <p class="tich-text tich-dept-header__meta">{{ $department->dept_code }}</p>
</header>

<article class="tich-card tich-dept-empty">
    <h2 class="tich-h3">No tools available yet</h2>
    <p class="tich-text tich-mt-2">There are no modules assigned for this department, or you do not have permission to access them.</p>
    <a href="{{ route('dashboard') }}" class="tich-btn tich-btn-secondary tich-mt-4">Back to main dashboard</a>
</article>
