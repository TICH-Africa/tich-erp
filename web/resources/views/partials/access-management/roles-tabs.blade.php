@props([
    'access',
    'section',
    'rolesCount',
    'categoriesCount',
])

<div class="tich-tabs tich-mb-8">
    <div class="tich-tabs__nav" style="justify-content: space-between; align-items: center;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <a
                href="{{ $access->route('roles.index') }}"
                class="tich-tabs__btn{{ $section === 'roles' ? ' is-active' : '' }}"
            >
                Roles
                <span class="tich-caption">({{ $rolesCount }})</span>
            </a>
            <a
                href="{{ $access->route('role-categories.index') }}"
                class="tich-tabs__btn{{ $section === 'categories' ? ' is-active' : '' }}"
            >
                Categories
                <span class="tich-caption">({{ $categoriesCount }})</span>
            </a>
        </div>
        @if ($section === 'roles')
            <button type="button" class="tich-btn tich-btn-primary role-create-trigger" data-open-modal="role-create-modal">
                Add role
            </button>
        @elseif ($section === 'categories')
            <button type="button" class="tich-btn tich-btn-primary category-create-trigger" data-open-modal="category-create-modal">
                Add category
            </button>
        @endif
    </div>
</div>
