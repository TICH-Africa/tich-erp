<?php

namespace App\Services;

use App\Models\ObjectiveAssessment;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns an objective CAT into the paper the lecturer hands out, plus the marker's copy that
 * carries the answer key.
 */
class ObjectivePaperDocumentService
{
    public const VIEW = 'staff.objective-papers.document';

    public function __construct(
        protected PrintDocumentService $printDocuments,
    ) {}

    /**
     * @param  'student'|'marker'  $copy
     * @param  array<string, mixed>  $overrides
     */
    public function page(ObjectiveAssessment $assessment, string $copy = 'student', array $overrides = []): View
    {
        return $this->printDocuments->render(self::VIEW, $this->data($assessment, $copy, $overrides));
    }

    /**
     * @param  'student'|'marker'  $copy
     * @param  array<string, mixed>  $overrides
     */
    public function download(ObjectiveAssessment $assessment, string $copy = 'student', array $overrides = []): StreamedResponse
    {
        $data = $this->data($assessment, $copy, $overrides);

        return $this->printDocuments->downloadPdf(self::VIEW, $data, $data['filename']);
    }

    /**
     * @param  'student'|'marker'  $copy
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(ObjectiveAssessment $assessment, string $copy, array $overrides = []): array
    {
        $assessment->loadMissing(['allocation.unit', 'allocation.staff', 'allocation.semester', 'questions']);

        $allocation = $assessment->allocation;
        $marker = $copy === 'marker';
        $letters = range('A', 'Z');

        $questions = $assessment->questions->values()->map(function ($question, int $index) use ($marker, $letters): array {
            $options = array_values(array_filter(
                array_map(static fn ($option) => trim((string) $option), $question->options ?? []),
                static fn (string $option) => $option !== ''
            ));

            return [
                'number' => $index + 1,
                'text' => (string) $question->question_text,
                'type' => (string) $question->question_type,
                'points' => (float) $question->points,
                'options' => collect($options)->map(fn (string $option, int $i) => [
                    'letter' => $letters[$i] ?? (string) ($i + 1),
                    'text' => $option,
                    'correct' => $marker && ! empty($question->correct_answer)
                        && strcasecmp($option, (string) $question->correct_answer) === 0,
                ])->values()->all(),
                'answer' => $marker ? $this->answerLabel($question->correct_answer, $options, $letters) : '',
                'is_subjective' => in_array($question->question_type, ['essay', 'long_answer', 'short_answer'], true),
            ];
        })->all();

        return array_merge([
            'assessment' => $assessment,
            'allocation' => $allocation,
            'copy' => $marker ? 'marker' : 'student',
            'questions' => $questions,
            'totalPoints' => round($assessment->questions->sum('points'), 2),
            'documentTitle' => $assessment->name,
            'documentSubtitle' => $marker
                ? "Marker's copy - shows the answer key"
                : trim((string) ($allocation?->unit?->unit_code.' '.$allocation?->unit?->unit_name)),
            'documentRef' => $this->printDocuments->documentRef('CAT', (string) ($allocation?->unit?->unit_code ?: $assessment->unit_id), (string) $assessment->id),
            'bodyClass' => 'tich-doc--cat',
            'filename' => 'cat-'.Str::slug($assessment->name).'-'.($marker ? 'markers-copy' : 'student').'.pdf',
        ], $overrides);
    }

    /**
     * @param  list<string>  $options
     * @param  list<string>  $letters
     */
    private function answerLabel(mixed $correct, array $options, array $letters): string
    {
        $value = trim((string) ($correct ?? ''));
        if ($value === '') {
            return 'Not set';
        }

        // A matching key is stored as a JSON pair map, so read it as pairs rather than as one string.
        if (str_starts_with($value, '{')) {
            $pairs = json_decode($value, true);
            if (is_array($pairs) && $pairs !== []) {
                return collect($pairs)
                    ->map(fn ($right, $left) => $left.' = '.$right)
                    ->implode('; ');
            }
        }

        foreach ($options as $i => $option) {
            if (strcasecmp($option, $value) === 0) {
                return ($letters[$i] ?? (string) ($i + 1)).' - '.$option;
            }
        }

        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtoupper($value) === 'TRUE' ? 'True' : 'False';
        }

        return $value;
    }
}
