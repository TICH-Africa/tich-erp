<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TranscriptDocumentService
{
    public function __construct(
        protected TranscriptService $transcripts,
        protected PrintDocumentService $printDocuments,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStudent(Student $student): array
    {
        return $this->transcripts->build($student);
    }

    /**
     * @return array<string, mixed>
     */
    public function forSemester(Student $student, int $semesterId): array
    {
        return $this->transcripts->buildForSemester($student, $semesterId);
    }

    public function filename(Student $student, ?int $semesterId = null): string
    {
        return 'transcript-'.Str::slug((string) $student->registration_number)
            .($semesterId ? '-term-'.$semesterId : '').'.pdf';
    }

    /**
     * @param  array<string, mixed>  $transcript
     */
    public function screen(array $transcript, ?string $backUrl = null, ?string $pdfUrl = null): View
    {
        return $this->printDocuments->render('sis.transcript.print', $this->documentData($transcript, $backUrl, $pdfUrl));
    }

    /**
     * @param  array<string, mixed>  $transcript
     */
    public function download(array $transcript, string $filename): StreamedResponse
    {
        return $this->printDocuments->downloadPdf(
            'sis.transcript.print',
            $this->documentData($transcript),
            $filename,
        );
    }

    /**
     * @param  array<string, mixed>  $transcript
     * @return array<string, mixed>
     */
    public function documentData(array $transcript, ?string $backUrl = null, ?string $pdfUrl = null): array
    {
        $student = $transcript['student'];
        $program = $transcript['program'];

        return [
            'transcript' => $transcript,
            'stats' => $this->stats($transcript),
            'gradeScale' => $this->gradeScale(),
            'documentTitle' => 'Official Academic Transcript',
            'documentSubtitle' => trim(($program?->program_code ?? '').'  '.($program?->program_name ?? 'Academic record')),
            'documentRef' => $this->printDocuments->documentRef('TR', (string) $student->registration_number),
            'bodyClass' => 'tich-doc--transcript',
            'paperOrientation' => 'portrait',
            'showSignatures' => true,
            'backUrl' => $backUrl,
            'pdfUrl' => $pdfUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $transcript
     * @return array<string, float|int>
     */
    public function stats(array $transcript): array
    {
        $rows = collect($transcript['rows'] ?? []);
        $graded = $rows->filter(fn ($row) => $row->grade_letter !== null && $row->grade_letter !== '');
        $passed = $graded->filter(fn ($row) => strtoupper((string) $row->grade_letter) !== 'F');

        return [
            'units_recorded' => (int) ($transcript['units_completed'] ?? $rows->count()),
            'units_graded' => $graded->count(),
            'units_passed' => $passed->count(),
            'credits_attempted' => round((float) $graded->sum('credit_hours'), 1),
            'credits_earned' => round((float) $passed->sum('credit_hours'), 1),
            'average_score' => $graded->isEmpty() ? null : round((float) $graded->avg('final_score'), 1),
            'cumulative_gpa' => round((float) ($transcript['cumulative_gpa'] ?? 0), 2),
            'semesters' => count($transcript['semester_blocks'] ?? []),
        ];
    }

    /**
     * @return array<int, array{letter: string, range: string, points: string}>
     */
    public function gradeScale(): array
    {
        return [
            ['letter' => 'A', 'range' => '70 and above', 'points' => '4.0'],
            ['letter' => 'B', 'range' => '60 - 69', 'points' => '3.0'],
            ['letter' => 'C', 'range' => '50 - 59', 'points' => '2.0'],
            ['letter' => 'D', 'range' => 'Pass mark to 49', 'points' => '1.0'],
            ['letter' => 'F', 'range' => 'Below the pass mark', 'points' => '0.0'],
        ];
    }
}
