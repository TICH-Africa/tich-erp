<x-page-toolbar title="Suggestion box" meta="Share suggestions, comments, or complaints with Academics" />

<div class="tich-grid tich-grid--2 tich-mt-8" style="gap: 1.5rem; align-items: start;">
    <article class="tich-card">
        <h2 class="tich-h3">Submit</h2>
        <p class="tich-text tich-mt-2">Your message goes to the Academics team. You can track the status below.</p>

        <form method="POST" action="{{ route('portal.suggestions.store') }}" class="tich-form-stack tich-mt-6">
            @csrf
            <div>
                <label for="category" class="tich-label">Type *</label>
                <select id="category" name="category" class="tich-select" required>
                    @foreach (\App\Models\StudentSuggestion::CATEGORIES as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')
                    <p class="tich-form-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="subject" class="tich-label">Subject</label>
                <input type="text" id="subject" name="subject" value="{{ old('subject') }}" class="tich-input" maxlength="200" placeholder="Short summary (optional)">
                @error('subject')
                    <p class="tich-form-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="body" class="tich-label">Message *</label>
                <textarea id="body" name="body" rows="6" class="tich-input" required maxlength="5000" placeholder="Describe your suggestion, comment, or complaint...">{{ old('body') }}</textarea>
                @error('body')
                    <p class="tich-form-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <button type="submit" class="tich-btn tich-btn-primary">Send to Academics</button>
            </div>
        </form>
    </article>

    <article class="tich-card">
        <h2 class="tich-h3">Your submissions</h2>
        @php
            $mySuggestions = \Illuminate\Support\Facades\Schema::hasTable('student_suggestions')
                ? $student->suggestions()->orderByDesc('created_at')->limit(20)->get()
                : collect();
        @endphp

        @forelse ($mySuggestions as $item)
            <div class="tich-mt-4" style="padding-bottom: 1rem; border-bottom: 1px solid var(--tich-border, #e2e4e5);">
                <div class="tich-flex-wrap" style="gap: 0.5rem; align-items: center; justify-content: space-between;">
                    <div>
                        <strong>{{ $item->subject ?: $item->categoryLabel() }}</strong>
                        <p class="tich-caption">{{ $item->categoryLabel() }} · {{ $item->created_at?->format('d M Y') }}</p>
                    </div>
                    <span class="tich-badge tich-badge--{{ $item->statusBadge() }}">{{ $item->statusLabel() }}</span>
                </div>
                <p class="tich-text tich-mt-2" style="white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($item->body, 220) }}</p>
                @if ($item->response)
                    <div class="tich-mt-3" style="padding: 0.75rem; background: var(--tich-neutral, #f5f6f6); border-radius: var(--radius-md, 0.5rem);">
                        <p class="tich-caption"><strong>Academics response</strong></p>
                        <p class="tich-text tich-mt-1" style="white-space: pre-wrap;">{{ $item->response }}</p>
                    </div>
                @endif
            </div>
        @empty
            <p class="tich-text tich-mt-4">You have not submitted anything yet.</p>
        @endforelse
    </article>
</div>
