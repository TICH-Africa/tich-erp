<article class="tich-card tich-mt-8">
    <h2 class="tich-h3">Invite employee to register on ERP</h2>
    <p class="tich-caption tich-mt-2">Enter the employee's personal email address. An invitation will be sent with a secure registration link, even if they are not yet in the staff directory. Submitting the same email again re-sends a fresh link and expires any previous unused invite.</p>

    <form method="POST" action="{{ $action }}" class="tich-mt-6" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:flex-end;">
        @csrf
        <div class="tich-form-group" style="flex:1 1 280px; margin:0;">
            <label for="registration_invite_email" class="tich-label">Personal email</label>
            <input
                type="email"
                id="registration_invite_email"
                name="email"
                value="{{ old('email') }}"
                required
                class="tich-input @error('email') tich-input--error @enderror"
                placeholder="employee@gmail.com"
            >
            @error('email')
                <p class="tich-field-error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="tich-btn tich-btn-primary">Send invitation</button>
    </form>
</article>
