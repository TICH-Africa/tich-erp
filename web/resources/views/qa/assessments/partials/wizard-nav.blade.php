@php
    $prev = max(1, $section - 1);
    $next = min(8, $section + 1);
@endphp

{{-- Hidden direction survives submit-once disabling of buttons (disabled controls are omitted from POST). --}}
<input type="hidden" name="direction" value="stay" data-iqa-direction>

<div class="tich-flex tich-flex--between tich-mt-8" style="gap:1rem;flex-wrap:wrap;">
    <div>
        @if ($section > 1)
            @if ($isPublishReview)
                <a href="{{ route('qa.assessments.publish-review', ['assessment' => $assessment, 'section' => $prev]) }}" class="tich-btn tich-btn-secondary">Back</a>
            @else
                <button
                    type="submit"
                    class="tich-btn tich-btn-secondary"
                    onclick="var d=this.form.querySelector('[data-iqa-direction]'); if(d) d.value='back';"
                >Back</button>
            @endif
        @else
            <span></span>
        @endif
    </div>
    <div class="tich-flex" style="gap:0.5rem;">
        @if (! $isPublishReview)
            <button
                type="submit"
                class="tich-btn tich-btn-ghost"
                onclick="var d=this.form.querySelector('[data-iqa-direction]'); if(d) d.value='stay';"
            >Save</button>
        @endif
        @if ($section < 8)
            @if ($isPublishReview)
                <a href="{{ route('qa.assessments.publish-review', ['assessment' => $assessment, 'section' => $next]) }}" class="tich-btn tich-btn-primary">Next</a>
            @else
                <button
                    type="submit"
                    class="tich-btn tich-btn-primary"
                    onclick="var d=this.form.querySelector('[data-iqa-direction]'); if(d) d.value='next';"
                >Next</button>
            @endif
        @elseif (! $isPublishReview)
            <button
                type="submit"
                class="tich-btn tich-btn-primary"
                onclick="var d=this.form.querySelector('[data-iqa-direction]'); if(d) d.value='publish-review';"
            >Review &amp; publish</button>
        @endif
    </div>
</div>
