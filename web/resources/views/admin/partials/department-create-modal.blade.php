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

            @if ($errors->any() && ! old('_method'))
                <div class="tich-modal__errors">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="tich-form-group">
                <label class="tich-label">Department code</label>
                <input type="text" name="dept_code" class="tich-input" value="{{ old('dept_code') }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Department name</label>
                <input type="text" name="dept_name" class="tich-input" value="{{ old('dept_name') }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Category</label>
                <select name="dept_category" class="tich-input" required>
                    @foreach ($deptCategories as $value => $label)
                        <option value="{{ $value }}" @selected(old('dept_category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Department group</label>
                <select name="department_group_id" class="tich-input">
                    <option value="">None</option>
                    @foreach ($departmentGroups as $group)
                        <option value="{{ $group->id }}" @selected(old('department_group_id') == $group->id)>{{ $group->group_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Parent department</label>
                <select name="parent_dept_id" class="tich-input">
                    <option value="">None (top level in group)</option>
                    @foreach ($parentDepartments as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_dept_id') == $parent->id)>{{ $parent->dept_name }}</option>
                    @endforeach
                </select>
                <p class="tich-caption tich-mt-1">Set parent to <em>Academics</em> for learning departments that offer programmes.</p>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Campus</label>
                <select name="campus_id" class="tich-input">
                    <option value="">Institution-wide</option>
                    @foreach ($campuses as $campus)
                        <option value="{{ $campus->id }}" @selected(old('campus_id') == $campus->id)>{{ $campus->campus_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tich-form-group">
                <label class="tich-label">Display order</label>
                <input type="number" name="display_order" class="tich-input" value="{{ old('display_order', 0) }}" min="0">
            </div>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="department-create-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Create department</button>
            </footer>
        </form>
    </div>
</div>

<style>
    .tich-modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .tich-modal.is-open {
        display: flex;
    }

    .tich-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(73, 76, 80, 0.55);
    }

    .tich-modal__dialog {
        position: relative;
        width: min(100%, 32rem);
        max-height: calc(100vh - 3rem);
        overflow: auto;
        background: var(--tich-surface, #fff);
        border-radius: 0.5rem;
        box-shadow: 0 1rem 2.5rem rgba(0, 0, 0, 0.18);
        border-top: 4px solid var(--tich-green, #6cab33);
    }

    .tich-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem 0;
    }

    .tich-modal__close {
        border: 0;
        background: transparent;
        font-size: 1.75rem;
        line-height: 1;
        color: #6b6e72;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
    }

    .tich-modal__close:hover {
        color: var(--tich-text, #494c50);
    }

    .tich-modal__body {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .tich-modal__errors {
        margin-bottom: 1rem;
        padding: 0.875rem 1rem;
        border: 1px solid #c0392b;
        border-radius: 0.375rem;
        background: rgba(192, 57, 43, 0.06);
    }

    .tich-modal__footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--tich-border, #e2e4e5);
    }
</style>

<script>
(function () {
    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-open-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openModal(trigger.getAttribute('data-open-modal'));
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            closeModal(trigger.getAttribute('data-close-modal'));
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('.tich-modal.is-open').forEach(function (modal) {
            closeModal(modal.id);
        });
    });
})();
</script>
