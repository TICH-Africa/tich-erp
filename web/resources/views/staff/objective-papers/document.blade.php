@extends('layouts.print-document')

@php
    $marker = $copy === 'marker';
    $unit = $allocation?->unit;
    $lecturer = $allocation?->staff;
@endphp

@section('document-content')
    <table class="tich-doc-head">
        <tbody>
            <tr>
                <th>UNIT CODE</th>
                <td>{{ $unit?->unit_code ?: '____________' }}</td>
                <th>UNIT</th>
                <td>{{ $unit?->unit_name ?: '____________________' }}</td>
            </tr>
            <tr>
                <th>ASSESSMENT</th>
                <td>{{ $assessment->name }}</td>
                <th>SEMESTER</th>
                <td>{{ $allocation?->semester?->semester_label ?: '____________' }}</td>
            </tr>
            @if ($marker)
                <tr>
                    <th>LECTURER</th>
                    <td>{{ $lecturer?->printedName() ?: '____________________' }}</td>
                    <th>STATUS</th>
                    <td>{{ ucfirst((string) $assessment->status) }}</td>
                </tr>
            @else
                <tr>
                    <th>STUDENT NAME</th>
                    <td class="tich-cat-rule">&nbsp;</td>
                    <th>REG. NO.</th>
                    <td class="tich-cat-rule">&nbsp;</td>
                </tr>
                <tr>
                    <th>DATE</th>
                    <td class="tich-cat-rule">&nbsp;</td>
                    <th>MARKS OBTAINED</th>
                    <td class="tich-cat-rule">&nbsp;</td>
                </tr>
            @endif
            <tr>
                <th>TIME ALLOWED</th>
                <td>{{ $assessment->time_limit_minutes ? $assessment->time_limit_minutes.' minutes' : 'As directed in class' }}</td>
                <th>TOTAL MARKS</th>
                <td class="num">{{ number_format($totalPoints, 1) }}</td>
            </tr>
            <tr>
                <th>SITTING WINDOW</th>
                <td colspan="3">
                    @if ($assessment->available_from || $assessment->available_until)
                        {{ $assessment->available_from?->format('d M Y H:i') ?: '____________' }}
                        to
                        {{ $assessment->available_until?->format('d M Y H:i') ?: '____________' }}
                    @else
                        As directed by the lecturer
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    @if ($marker)
        <p class="tich-cat-band tich-cat-band--key">
            Marker's copy &mdash; carries the answer key. Do not hand this copy to students.
        </p>
    @else
        <p class="tich-cat-band">
            Answer ALL questions. Write your answer clearly in the space provided.
            Marks are awarded as indicated on each question.
        </p>
    @endif

    <table class="tich-doc-table tich-cat-table">
        <thead>
            <tr>
                <th style="width:7%;">NO.</th>
                <th>QUESTION</th>
                @if ($marker)
                    <th style="width:24%;">ANSWER KEY</th>
                @else
                    <th style="width:12%;">ANSWER</th>
                @endif
                <th style="width:11%;">MARKS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($questions as $question)
                <tr>
                    <td class="num">{{ $question['number'] }}</td>
                    <td>
                        <p class="tich-cat-question">{{ $question['text'] }}</p>

                        @if ($question['options'] !== [])
                            <table class="tich-cat-options">
                                <tbody>
                                    @foreach ($question['options'] as $option)
                                        <tr>
                                            <td class="tich-cat-letter">{{ $option['letter'] }})</td>
                                            <td @class(['tich-cat-correct' => $option['correct']])>{{ $option['text'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif ($question['type'] === 'true_false')
                            <p class="tich-cat-choices">True &nbsp;&nbsp;/&nbsp;&nbsp; False</p>
                        @endif

                        @if (! $marker && $question['is_subjective'])
                            <table class="tich-cat-lines">
                                @for ($line = 0; $line < 4; $line++)
                                    <tr><td class="tich-cat-line">&nbsp;</td></tr>
                                @endfor
                            </table>
                        @elseif (! $marker && $question['type'] === 'matching')
                            <table class="tich-cat-lines">
                                @for ($line = 0; $line < 3; $line++)
                                    <tr><td class="tich-cat-line">&nbsp;</td></tr>
                                @endfor
                            </table>
                        @endif
                    </td>
                    @if ($marker)
                        <td class="tich-cat-key">{{ $question['answer'] }}</td>
                    @else
                        <td class="tich-cat-answer">&nbsp;</td>
                    @endif
                    <td class="num">{{ number_format($question['points'], 1) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        This paper has no questions yet. Add the questions on the assessment screen before printing.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <th>
                    TOTAL &mdash; {{ count($questions) }}
                    {{ \Illuminate\Support\Str::plural('question', count($questions)) }}
                </th>
                <td></td>
                <td class="num">{{ number_format($totalPoints, 1) }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($marker)
        <table class="tich-doc-head tich-cat-signoff">
            <tbody>
                <tr>
                    <th>MODERATED BY</th>
                    <td class="tich-cat-rule">&nbsp;</td>
                    <th>SIGNATURE / DATE</th>
                    <td class="tich-cat-rule">&nbsp;</td>
                </tr>
            </tbody>
        </table>
    @endif
@endsection
