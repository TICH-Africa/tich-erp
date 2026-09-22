@php
    $prev = max(1, $section - 1);
    $next = min(8, $section + 1);
@endphp

<div class="tich-flex tich-flex--between tich-mt-8" style="gap:1rem;flex-wrap:wrap;">
    <div>
        @if ($section > 1)
            @if ($isPublishReview)
                <a href="{{ route('qa.assessments.publish-review', ['assessment' => $assessment, 'section' => $prev]) }}" class="tich-btn tich-btn-secondary">Back</a>
            @else
                <button type="submit" name="direction" value="back" class="tich-btn tich-btn-secondary">Back</button>
            @endif
        @else
            <span></span>
        @endif
    </div>
    <div class="tich-flex" style="gap:0.5rem;">
        @if (! $isPublishReview)
            <button type="submit" name="direction" value="stay" class="tich-btn tich-btn-ghost">Save</button>
        @endif
        @if ($section < 8)
            @if ($isPublishReview)
                <a href="{{ route('qa.assessments.publish-review', ['assessment' => $assessment, 'section' => $next]) }}" class="tich-btn tich-btn-primary">Next</a>
            @else
                <button type="submit" name="direction" value="next" class="tich-btn tich-btn-primary">Next</button>
            @endif
        @elseif (! $isPublishReview)
            <button type="submit" name="direction" value="publish-review" class="tich-btn tich-btn-primary">Review &amp; publish</button>
        @endif
    </div>
</div>
