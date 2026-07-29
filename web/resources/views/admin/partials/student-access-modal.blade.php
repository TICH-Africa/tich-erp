@props([
    'studentRoles',
    'openUserId' => null,
])

<div
    id="student-access-modal"
    class="tich-modal{{ $openUserId ? ' is-open' : '' }}"
    aria-hidden="{{ $openUserId ? 'false' : 'true' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="student-access-modal-title"
>
    <div class="tich-modal__backdrop" data-close-modal="student-access-modal"></div>
    <div class="tich-modal__dialog" style="max-width: 28rem;">
        <header class="tich-modal__header">
            <h2 id="student-access-modal-title" class="tich-h3" style="margin: 0;">Student portal role</h2>
            <button type="button" class="tich-modal__close" data-close-modal="student-access-modal" aria-label="Close">&times;</button>
        </header>

        <form
            id="student-access-form"
            method="POST"
            action="{{ $openUserId ? route('admin.users.update', $openUserId) : '#' }}"
            class="tich-modal__body"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="audience" value="students">
            <input type="hidden" name="edit_user_id" value="{{ old('edit_user_id') }}">

            <p class="tich-text tich-mb-4" id="student-access-user-meta"></p>

            @if ($errors->any() && old('audience') === 'students')
                <div class="tich-modal__errors tich-mb-4">
                    <ul style="margin: 0; padding-left: 1.25rem;">
                        @foreach ($errors->all() as $error)
                            <li class="tich-text">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="tich-form-group">
                <label class="tich-label">Role</label>
                <select name="assignments[0][role_id]" id="student-access-role" class="tich-input" required>
                    <option value="">Select role…</option>
                    @foreach ($studentRoles as $role)
                        <option value="{{ $role->id }}" @selected(old('assignments.0.role_id') == $role->id)>{{ $role->role_name }}</option>
                    @endforeach
                </select>
            </div>

            <p class="tich-caption tich-mt-3">Programme and academic department are managed in admissions and the SIS.</p>

            <footer class="tich-modal__footer">
                <button type="button" class="tich-btn tich-btn-secondary" data-close-modal="student-access-modal">Cancel</button>
                <button type="submit" class="tich-btn tich-btn-primary">Save</button>
            </footer>
        </form>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('student-access-form');
    if (!form) return;

    document.querySelectorAll('.student-access-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            form.action = trigger.getAttribute('data-update-url') || '#';
            document.getElementById('student-access-modal-title').textContent = 'Student role — ' + (trigger.getAttribute('data-username') || '');
            document.getElementById('student-access-user-meta').textContent = trigger.getAttribute('data-email') || '';

            var roleId = trigger.getAttribute('data-role-id') || '';
            document.getElementById('student-access-role').value = roleId;
        });
    });

    @if ($openUserId)
        document.body.style.overflow = 'hidden';
    @endif
})();
</script>
