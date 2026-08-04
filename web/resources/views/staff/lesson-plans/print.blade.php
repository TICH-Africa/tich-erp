@extends('layouts.print-document')

@section('document-content')
    @php
        $payload = $payload ?? [];
        $sessionRows = $payload['session_rows'] ?? [];
        $rowColumns = config('tich-lesson-plans.session_row_columns', []);
    @endphp

    <div class="tich-lesson-plan">
        <section class="tich-lesson-plan__section">
            <h2 class="tich-lesson-plan__heading">Objectives</h2>
            <dl class="tich-lesson-plan__dl">
                <dt>General objective</dt>
                <dd>{!! nl2br(e($payload['general_objective'] ?? '-')) !!}</dd>
                <dt>Specific objectives</dt>
                <dd>{!! nl2br(e($plan->lesson_objectives ?? '-')) !!}</dd>
                <dt>Key competencies</dt>
                <dd>{!! nl2br(e($plan->competencies_targeted ?? '-')) !!}</dd>
                <dt>Prior knowledge</dt>
                <dd>{!! nl2br(e($payload['prior_knowledge'] ?? '-')) !!}</dd>
                <dt>Resources / materials</dt>
                <dd>{!! nl2br(e($plan->resources_required ?? '-')) !!}</dd>
            </dl>
        </section>

        @if ($sessionRows !== [])
            <section class="tich-lesson-plan__section">
                <h2 class="tich-lesson-plan__heading">Lesson session plan</h2>
                <table class="tich-lesson-plan__table">
                    <thead>
                        <tr>
                            @foreach ($rowColumns as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessionRows as $row)
                            <tr>
                                @foreach (array_keys($rowColumns) as $columnKey)
                                    <td>{!! nl2br(e($row[$columnKey] ?? '')) !!}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        @if (! empty($payload['assignment']) || ! empty($payload['references']))
            <section class="tich-lesson-plan__section">
                <h2 class="tich-lesson-plan__heading">Follow-up</h2>
                <dl class="tich-lesson-plan__dl">
                    @if (! empty($payload['assignment']))
                        <dt>Assignment / homework</dt>
                        <dd>{!! nl2br(e($payload['assignment'])) !!}</dd>
                    @endif
                    @if (! empty($payload['references']))
                        <dt>References</dt>
                        <dd>{!! nl2br(e($payload['references'])) !!}</dd>
                    @endif
                </dl>
            </section>
        @endif

        <section class="tich-lesson-plan__signatures">
            <div class="tich-lesson-plan__signature">
                <p class="tich-lesson-plan__signature-line"></p>
                <p><strong>Facilitator / tutor</strong></p>
                <p>{{ $tutor?->fullName() ?? '-' }}</p>
                @if ($plan->tutor_verified_at)
                    <p class="tich-caption">Verified {{ $plan->tutor_verified_at->format('d M Y H:i') }}</p>
                @endif
            </div>
            <div class="tich-lesson-plan__signature">
                <p class="tich-lesson-plan__signature-line"></p>
                <p><strong>Head of department</strong></p>
                <p>Signature &amp; stamp</p>
            </div>
            <div class="tich-lesson-plan__signature">
                <p class="tich-lesson-plan__signature-line"></p>
                <p><strong>QA officer</strong></p>
                <p>Signature &amp; stamp</p>
            </div>
            <div class="tich-lesson-plan__signature">
                <p class="tich-lesson-plan__signature-line"></p>
                <p><strong>Academic registrar</strong></p>
                <p>Signature &amp; stamp</p>
            </div>
        </section>
    </div>
@endsection
