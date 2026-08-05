<x-page-toolbar
    title="Departments"
    meta="Learning and operational units under {{ $department->dept_name }}"
/>

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
