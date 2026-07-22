@props([
    'campuses',
    'departmentGroups',
    'parentDepartments',
    'deptCategories',
    'open' => false,
])

<div
    id="department-create-modal"
    class="tich-modal{{ $open ? ' is-open' : '' }}"
    aria-hidden="{{ $open ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="department-create-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="department-create-modal"></div>

    <div class="tich-modal__dialog">
        <header class="tich-modal__header">
            <h2 id="department-create-modal-title" class="tich-h3" style="margin: 0;">Add department</h2>
            <button type="button" class="tich-modal__close" data-close-modal="department-create-modal" aria-label="Close">&times;</button>
        </header>

        <form method="POST" action="{{ route('admin.departments.store') }}" class="tich-modal__body">
            @csrf

            @if ($errors->any() && old('_method') !== 'PUT')
                <div class="tich-modal__errors">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('admin.partials.department-form-fields', [
                'campuses' => $campuses,
                'departmentGroups' => $departmentGroups,
                'parentDepartments' => $parentDepartments,
                'deptCategories' => $deptCategories,
            ])

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="department-create-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Create department</button>
            </footer>
        </form>
    </div>
</div>
