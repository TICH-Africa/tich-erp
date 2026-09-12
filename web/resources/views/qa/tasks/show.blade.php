@php
    $moduleContext = $moduleContext ?? \App\Support\QaTaskModuleContext::forModule('qa');
    $taskRoutes = $taskRoutes ?? $moduleContext['routes'];
    $respondentDepartment = $respondentDepartment ?? $department;
    $readOnly = (bool) ($readOnly ?? false);
@endphp

@extends($moduleContext['layout'])

@section('title', $readOnly ? 'View QA assessment' : 'Complete QA assessment')

@section($moduleContext['content_section'])
    <x-page-toolbar title="{{ $plan->plan_name }}" meta="{{ $respondentDepartment->dept_name }}{{ $readOnly ? ' · Submitted' : '' }}">
        <x-slot:actions>
            <a href="{{ \App\Support\QaTaskModuleContext::url('index', $moduleContext['key']) }}" class="tich-btn tich-btn-ghost">Back to tasks</a>
        </x-slot:actions>
    </x-page-toolbar>

    @error('task')
        <div class="tich-alert tich-alert--error tich-mt-4">{{ $message }}</div>
    @enderror

    @if ($readOnly)
        <div class="tich-alert tich-alert--info tich-mt-4">This assessment has been submitted to Quality Assurance. You can view responses below, but they can no longer be edited.</div>
    @endif

    @if ($plan->instructions)
        <div class="tich-alert tich-alert--info tich-mt-4">{{ $plan->instructions }}</div>
    @endif

    @if ($readOnly)
        <div class="tich-mt-6">
            @foreach ($plan->checklists as $item)
                @php $submission = $submissions->get($item->id); @endphp
                <div class="tich-card tich-mt-4">
                    <h2 class="tich-h3">{{ $item->display_order ?: $loop->iteration }}. {{ $item->checklist_item_text }}</h2>
                    <p class="tich-caption tich-mt-1">
                        @if ($item->item_category){{ $item->item_category }} · @endif
                        Max {{ (int) $item->max_score }} · Weight {{ $item->weight }}
                        @if ($submission)
                            · Status: {{ $submission->submission_status }}
                        @endif
                    </p>
                    <p class="tich-text tich-mt-4">{{ $submission->submission_text ?: '—' }}</p>
                    <p class="tich-caption">Score: {{ $submission && $submission->score !== null ? (int) $submission->score : '-' }}</p>
                    @if ($submission?->evidence?->isNotEmpty())
                        <ul class="tich-mt-2" style="margin:0;padding-left:1.1rem;">
                            @foreach ($submission->evidence as $file)
                                <li><a href="{{ route('qa.evidence.viewer', $file) }}" class="tich-link" target="_blank" rel="noopener">{{ $file->description ?: 'Attachment' }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <form method="POST" action="{{ \App\Support\QaTaskModuleContext::url('store', $moduleContext['key'], ['plan' => $plan, 'department' => $respondentDepartment]) }}" enctype="multipart/form-data" class="tich-mt-6">
            @csrf
            <input type="hidden" name="save_action" id="qa-save-action" value="draft">

            @foreach ($plan->checklists as $item)
                @php $submission = $submissions->get($item->id); @endphp
                <div class="tich-card tich-mt-4">
                    <h2 class="tich-h3">{{ $item->display_order ?: $loop->iteration }}. {{ $item->checklist_item_text }}</h2>
                    <p class="tich-caption tich-mt-1">
                        @if ($item->item_category){{ $item->item_category }} · @endif
                        Max {{ (int) $item->max_score }} · Weight {{ $item->weight }}
                        @if ($item->requires_evidence) · Evidence required @endif
                        @if ($submission)
                            · Status: {{ $submission->submission_status }}
                        @endif
                    </p>

                    @if ($submission && ! $submission->isEditable())
                        <p class="tich-text tich-mt-4">{{ $submission->submission_text }}</p>
                        <p class="tich-caption">Score: {{ $submission->score !== null ? (int) $submission->score : '-' }}</p>
                        @if ($submission->evidence?->isNotEmpty())
                            <ul class="tich-mt-2" style="margin:0;padding-left:1.1rem;">
                                @foreach ($submission->evidence as $file)
                                    <li><a href="{{ route('qa.evidence.viewer', $file) }}" class="tich-link" target="_blank" rel="noopener">{{ $file->description ?: 'Attachment' }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    @else
                        <div class="tich-form-grid tich-form-grid--2 tich-mt-4">
                            <div class="tich-form-group" style="grid-column:1/-1;">
                                <label class="tich-label">Response</label>
                                <textarea
                                    name="answers[{{ $item->id }}][submission_text]"
                                    class="tich-input"
                                    rows="3"
                                >{{ old("answers.{$item->id}.submission_text", $submission->submission_text ?? '') }}</textarea>
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Score (0–{{ (int) $item->max_score }})</label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    max="{{ (int) $item->max_score }}"
                                    name="answers[{{ $item->id }}][score]"
                                    class="tich-input"
                                    value="{{ old("answers.{$item->id}.score", $submission->score ?? '') }}"
                                >
                            </div>
                            <div class="tich-form-group">
                                <label class="tich-label">Attach Files</label>
                                <input type="file" name="evidence[{{ $item->id }}][]" class="tich-input" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                                @if ($submission?->evidence?->isNotEmpty())
                                    <ul class="tich-mt-2" style="margin:0;padding-left:1.1rem;">
                                        @foreach ($submission->evidence as $file)
                                            <li><a href="{{ route('qa.evidence.viewer', $file) }}" class="tich-link" target="_blank" rel="noopener">{{ $file->description ?: 'Attachment' }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="tich-flex-wrap tich-mt-6" style="gap:0.75rem;">
                <button
                    type="submit"
                    class="tich-btn tich-btn-secondary"
                    onclick="document.getElementById('qa-save-action').value='draft';"
                >Save draft</button>
                <button
                    type="submit"
                    class="tich-btn tich-btn-primary"
                    onclick="if (!confirm('Submit final responses to Quality Assurance? You will not be able to edit after submitting.')) { return false; } document.getElementById('qa-save-action').value='submit';"
                >Submit to QA</button>
            </div>
        </form>
    @endif
@endsection
