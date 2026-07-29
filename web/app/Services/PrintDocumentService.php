<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PrintDocumentService
{
    /**
     * @return array<string, mixed>
     */
    public function institution(): array
    {
        $site = config('tich-navigation.site', []);
        $address = collect(config('tich-navigation.contact', []))
            ->firstWhere('channel_type', 'physical_address');

        return [
            'name' => $site['institution_name'] ?? 'Tropical Institute of Community Health and Development in Africa',
            'short_name' => $site['short_name'] ?? 'TICH in Africa',
            'tagline' => $site['tagline'] ?? 'Community health education for Africa',
            'address' => $address['display_value'] ?? 'Kisumu, Kenya',
            'copyright' => $site['copyright'] ?? ($site['institution_name'] ?? 'TICH in Africa'),
            'website' => $site['website'] ?? 'tich.africa',
        ];
    }

    public function normalizeDocumentText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return str_replace(['—', '–', '−'], '-', $text);
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
            'institution' => $this->institution(),
            'generatedAt' => now(),
        ], $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function downloadPdf(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $html = view($view, array_merge([
            'institution' => $this->institution(),
            'generatedAt' => now(),
            'forPdf' => true,
        ], $data))->render();

        return Pdf::loadHTML($this->normalizeDocumentHtml($html))
            ->setPaper('a4', $orientation)
            ->download($filename);
    }
}
