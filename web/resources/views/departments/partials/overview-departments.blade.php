<header class="tich-dept-header">
    <p class="tich-caption">{{ $overviewStats['category'] }}</p>
    <h1 class="tich-h1 tich-dept-header__title">Departments</h1>
    <p class="tich-text tich-dept-header__meta">
        Learning and operational units under {{ $department->dept_name }}.
    </p>
</header>

<div class="tich-grid tich-grid--3 tich-dept-cards">
    @foreach ($childDepartments as $child)
        <article class="tich-card tich-dept-card">
            <p class="tich-caption">{{ $categoryLabel($child) }}</p>
            <h3 class="tich-h3 tich-mt-2">{{ $child->dept_name }}</h3>
            <p class="tich-text tich-mt-2">{{ $cardDescription($child) }}</p>
                <a href="{{ $entryUrl($child) }}" class="tich-btn tich-btn-primary tich-mt-4">Open department dashboard</a>
        </article>
    @endforeach
</div>
