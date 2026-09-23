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

        return str_replace(['-', '-', '−'], '-', $text);
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
     * @param  array<string, mixed>  $options  Optional mPDF overrides (e.g. default_font, fontDir, fontdata)
     */
    public function downloadPdf(string $view, array $data, string $filename, string $orientation = 'portrait', array $options = []): StreamedResponse
    {
        $mpdf = $this->makePdf($view, $data, $orientation, $options);

        return response()->streamDownload(function () use ($mpdf, $filename) {
            $mpdf->Output($filename, 'D');
        }, $filename);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     */
    public function inlinePdf(string $view, array $data, string $filename, string $orientation = 'portrait', array $options = []): Response
    {
        $mpdf = $this->makePdf($view, $data, $orientation, $options);

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Website table body stack is Merriweather → Georgia → serif.
     * Register Georgia from the OS fonts folder when available (no project asset copy).
     *
     * @return array<string, mixed>
     */
    public function websiteTableFontOptions(): array
    {
        $windowsFonts = 'C:\\Windows\\Fonts';
        $regular = $windowsFonts.DIRECTORY_SEPARATOR.'georgia.ttf';
        $bold = $windowsFonts.DIRECTORY_SEPARATOR.'georgiab.ttf';

        if (! is_file($regular)) {
            return [];
        }

        $defaultConfig = (new \Mpdf\Config\ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $fontDirs[] = $windowsFonts;

        $defaultFontConfig = (new \Mpdf\Config\FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];
        $fontData['tichbody'] = [
            'R' => 'georgia.ttf',
            'B' => is_file($bold) ? 'georgiab.ttf' : 'georgia.ttf',
            'I' => is_file($windowsFonts.DIRECTORY_SEPARATOR.'georgiai.ttf') ? 'georgiai.ttf' : 'georgia.ttf',
            'BI' => is_file($windowsFonts.DIRECTORY_SEPARATOR.'georgiaz.ttf') ? 'georgiaz.ttf' : (is_file($bold) ? 'georgiab.ttf' : 'georgia.ttf'),
        ];

        return [
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'default_font' => 'tichbody',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $options
     */
    private function makePdf(string $view, array $data, string $orientation = 'portrait', array $options = []): Mpdf
    {
        $html = view($view, array_merge([
            'institution' => $this->institution(true),
            'generatedAt' => now(),
            'forPdf' => true,
        ], $data))->render();

        $config = array_merge([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => $orientation === 'landscape' ? 'L' : 'P',
            'default_font_size' => 9,
        ], $options);

        $mpdf = new Mpdf($config);
        if (! empty($config['default_font'])) {
            $mpdf->SetDefaultFont($config['default_font']);
        }
        $mpdf->SetDefaultFontSize((float) ($config['default_font_size'] ?? 9));
        // Prevent wide checklist tables from auto-shrinking to a smaller font than sampling tables.
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->keep_table_proportions = true;
        $mpdf->WriteHTML($this->normalizeDocumentHtml($html));

        return $mpdf;
    }
}
