<?php

namespace App\Services;

use Mpdf\Mpdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class PrintDocumentService
{
    public function __construct(
        protected SiteSettingsService $siteSettings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function institution(bool $forPdf = false): array
    {
        return $this->siteSettings->documentBranding($forPdf);
    }

    public function normalizeDocumentText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return str_replace(['-', '–', '−'], '-', $text);
    }

    public function normalizeDocumentHtml(string $html): string
    {
        return $this->normalizeDocumentText($html);
    }

    public function documentRef(string $prefix, string|int ...$parts): string
    {
        $slug = collect($parts)
            ->map(fn ($part) => Str::upper(Str::slug((string) $part, '-')))
            ->implode('-');

        return $prefix.'-'.$slug.'-'.now()->format('Ymd');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data): View
    {
        return view($view, array_merge([
            'institution' => $this->institution(false),
            'generatedAt' => now(),
        ], $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function downloadPdf(string $view, array $data, string $filename, string $orientation = 'portrait'): StreamedResponse
    {
        $mpdf = $this->makePdf($view, $data, $orientation);

        return response()->streamDownload(function () use ($mpdf, $filename) {
            $mpdf->Output($filename, 'D');
        }, $filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function inlinePdf(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $mpdf = $this->makePdf($view, $data, $orientation);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makePdf(string $view, array $data, string $orientation = 'portrait'): Mpdf
    {
        $html = view($view, array_merge([
            'institution' => $this->institution(true),
            'generatedAt' => now(),
            'forPdf' => true,
        ], $data))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => $orientation === 'landscape' ? 'L' : 'P',
        ]);
        $mpdf->WriteHTML($this->normalizeDocumentHtml($html));

        return $mpdf;
    }
}
