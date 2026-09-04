@php
    $evaluationWindows = $evaluationWindows ?? collect();
    $myEvaluations = $myEvaluations ?? collect();
@endphp

<x-page-toolbar title="Course evaluations" meta="Share feedback while evaluation windows are open" />

@forelse ($evaluationWindows as $window)
    <article class="tich-card tich-mt-8">
        <h2 class="tich-h3">{{ $window->title }}</h2>
        <p class="tich-caption tich-mt-2">
            Open {{ $window->opens_at?->format('d M Y H:i') }} – {{ $window->closes_at?->format('d M Y H:i') }}
        </p>
        <form method="POST" action="{{ route('portal.evaluations.store') }}" class="tich-form-stack tich-mt-4">
            @csrf
            <input type="hidden" name="window_id" value="{{ $window->id }}">
            <div>
                <label for="rating-{{ $window->id }}" class="tich-label">Overall rating (1–5)</label>
                <select id="rating-{{ $window->id }}" name="rating" class="tich-select" required>
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="comments-{{ $window->id }}" class="tich-label">Comments</label>
                <textarea id="comments-{{ $window->id }}" name="comments" rows="4" class="tich-input">{{ old('comments') }}</textarea>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Submit evaluation</button>
        </form>
    </article>
@empty
    <div class="tich-mt-8">
        @include('partials.states.empty', [
            'title' => 'No open evaluation windows',
            'description' => 'When Academics opens a course evaluation period, you will be able to submit feedback here.',
            'icon' => 'inbox',
        ])
    </div>
@endforelse

@if ($myEvaluations->isNotEmpty())
    <section class="tich-portal-panel tich-mt-8">
        <div class="tich-portal-panel__head">
            <h2 class="tich-h3">Your submissions</h2>
        </div>
        <div class="tich-card tich-table-panel tich-mt-4">
            <table class="tich-admin-table">
                <thead>
                    <tr>
                        <th>Window</th>
                        <th>Rating</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($myEvaluations as $evaluation)
                        <tr>
                            <td>#{{ $evaluation->window_id }}</td>
                            <td>{{ $evaluation->rating }}/5</td>
                            <td class="tich-caption">{{ $evaluation->submitted_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
