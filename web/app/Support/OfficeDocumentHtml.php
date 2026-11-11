<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Throwable;
use ZipArchive;

/**
 * Renders an uploaded Word or Excel file as browser HTML. One reader for timetables and lesson notes.
 */
class OfficeDocumentHtml
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public const PREVIEW_EXTENSIONS = ['docx', 'xlsx', 'xls', 'csv'];

    private const MAX_BLOCKS = 400;

    private const MAX_TABLE_ROWS = 300;

    public function __construct(
        private readonly SpreadsheetTabularReader $sheets,
    ) {}

    public function canPreview(string $extension): bool
    {
        return in_array(strtolower($extension), self::PREVIEW_EXTENSIONS, true);
    }

    /**
     * HTML of the file at the given path, or null when it cannot be read.
     */
    public function html(string $path, string $extension): ?string
    {
        if (! is_file($path) || ! $this->canPreview($extension)) {
            return null;
        }

        try {
            $html = $extension === 'docx' ? $this->wordHtml($path) : $this->sheetHtml($path, $extension);
        } catch (Throwable) {
            return null;
        }

        return is_string($html) && trim($html) !== '' ? $html : null;
    }

    /**
     * Plain reason shown when the file can be uploaded but not previewed.
     */
    public function fallbackReason(string $extension): ?string
    {
        return match (strtolower($extension)) {
            'doc' => 'This is an old Word 97 document, which browsers cannot display. Open or download it, or upload the .docx version for inline preview.',
            'pptx', 'ppt' => 'This is a presentation file, which browsers cannot display. Open or download it.',
            default => null,
        };
    }

    private function wordHtml(string $path): ?string
    {
        if (! class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $loaded = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        $body = $xpath->query('//w:body')->item(0);
        if (! $body instanceof DOMElement) {
            return null;
        }

        $blocks = [];
        $truncated = false;

        foreach ($body->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if (count($blocks) >= self::MAX_BLOCKS) {
                $truncated = true;
                break;
            }

            if ($node->localName === 'p') {
                $blocks[] = $this->wordParagraph($xpath, $node);
            } elseif ($node->localName === 'tbl') {
                $table = $this->wordTable($xpath, $node);
                if ($table !== null) {
                    $blocks[] = $table;
                }
            }
        }

        if ($blocks === []) {
            return null;
        }

        if ($truncated) {
            $blocks[] = '<p class="doc-viewer__doc-note">The document is long, so the preview stops here. Use the download button for the full file.</p>';
        }

        return '<div class="doc-viewer__doc">'.implode('', $blocks).'</div>';
    }

    private function wordParagraph(DOMXPath $xpath, DOMElement $paragraph): string
    {
        $text = $this->mergeRuns($this->wordRuns($xpath, $paragraph));

        if (trim(strip_tags($text)) === '') {
            return '<div class="doc-viewer__doc-gap"></div>';
        }

        $style = $xpath->query('./w:pPr/w:pStyle', $paragraph)->item(0);
        $styleName = strtolower(trim($style instanceof DOMElement ? $this->wordValue($style) : ''));

        if (preg_match('/^(?:heading|title)([1-6])$/', $styleName, $matches) === 1) {
            $level = min(max((int) $matches[1] + 2, 3), 6);

            return '<h'.$level.' class="doc-viewer__doc-heading">'.$text.'</h'.$level.'>';
        }

        if (preg_match('/^heading[1-3]$|^title$/', $styleName) === 1) {
            return '<h4 class="doc-viewer__doc-heading">'.$text.'</h4>';
        }

        return '<p class="doc-viewer__doc-para">'.$text.'</p>';
    }

    /**
     * Word splits one styled phrase across runs; fold the repeated tags back together.
     */
    private function mergeRuns(string $html): string
    {
        do {
            $before = $html;
            $html = str_replace(['</strong><strong>', '</em><em>'], '', $html);
        } while ($html !== $before);

        return $html;
    }

    /**
     * Escaped inline HTML built from every run under the node, in document order.
     */
    private function wordRuns(DOMXPath $xpath, DOMElement $node): string
    {
        $html = '';

        $runs = $xpath->query('.//w:r', $node);

        foreach ($runs as $run) {
            if (! $run instanceof DOMElement) {
                continue;
            }

            // Deleted text is not part of the document once the change is rejected.
            if ($run->parentNode instanceof DOMElement && $run->parentNode->localName === 'del') {
                continue;
            }

            $bold = $xpath->query('./w:rPr/w:b', $run)->length > 0;
            $italic = $xpath->query('./w:rPr/w:i', $run)->length > 0;

            foreach ($run->childNodes as $child) {
                if (! $child instanceof DOMElement) {
                    continue;
                }

                $piece = match ($child->localName) {
                    't' => e($child->textContent, false),
                    'tab' => '&nbsp;&nbsp;',
                    'br' => '<br>',
                    default => '',
                };

                if ($piece === '') {
                    continue;
                }

                if ($bold) {
                    $piece = '<strong>'.$piece.'</strong>';
                }
                if ($italic) {
                    $piece = '<em>'.$piece.'</em>';
                }

                $html .= $piece;
            }
        }

        return $html;
    }

    /**
     * @return array{text: string, colspan: int, continued: bool}|null
     */
    private function wordCell(DOMXPath $xpath, DOMElement $cell): ?array
    {
        $text = '';

        foreach ($xpath->query('./w:p', $cell) as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $line = $this->mergeRuns($this->wordRuns($xpath, $paragraph));
            if (trim(strip_tags($line)) !== '') {
                $text .= ($text === '' ? '' : '<br>').$line;
            }
        }

        $span = $xpath->query('./w:tcPr/w:gridSpan', $cell)->item(0);
        $merge = $xpath->query('./w:tcPr/w:vMerge', $cell)->item(0);
        $mergeValue = $merge instanceof DOMElement ? strtolower($this->wordValue($merge)) : '';

        return [
            'text' => $text,
            'colspan' => $span instanceof DOMElement ? max(1, (int) $this->wordValue($span)) : 1,
            'continued' => $merge instanceof DOMElement && ! in_array($mergeValue, ['restart', 'on', 'true', '1'], true),
        ];
    }

    private function wordTable(DOMXPath $xpath, DOMElement $table): ?string
    {
        $rows = [];

        foreach ($xpath->query('./w:tr', $table) as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            if (count($rows) >= self::MAX_TABLE_ROWS) {
                break;
            }

            $cells = [];
            $width = 0;

            foreach ($xpath->query('./w:tc', $row) as $cell) {
                if (! $cell instanceof DOMElement) {
                    continue;
                }

                $parsed = $this->wordCell($xpath, $cell);
                if ($parsed === null) {
                    continue;
                }

                $cells[] = $parsed;
                $width += $parsed['colspan'];
            }

            if ($cells === []) {
                continue;
            }

            $rows[] = ['cells' => $cells, 'width' => $width];
        }

        if ($rows === []) {
            return null;
        }

        // A vertical merge spans the cells below that continue it, then drops out.
        foreach ($rows as $rowIndex => $row) {
            $column = 0;

            foreach ($row['cells'] as $cellIndex => $cell) {
                if ($cell['continued']) {
                    $rows[$rowIndex]['cells'][$cellIndex]['skip'] = true;
                    $column += $cell['colspan'];
                    continue;
                }

                $span = 1;
                $below = $rowIndex + 1;

                while ($below < count($rows)) {
                    $next = $this->cellAtColumn($rows[$below], $column);
                    if ($next !== null && ($next['continued'] ?? false)) {
                        $span++;
                        $below++;
                        continue;
                    }
                    break;
                }

                $rows[$rowIndex]['cells'][$cellIndex]['rowspan'] = $span;
                $column += $cell['colspan'];
            }
        }

        $headerIndex = 0;
        $firstHasText = false;
        foreach ($rows[0]['cells'] as $cell) {
            if (trim(strip_tags($cell['text'])) !== '') {
                $firstHasText = true;
                break;
            }
        }

        // Each table scrolls sideways on its own so a phone can swipe the days without losing the page.
        $markup = '<div class="doc-viewer__doc-scroll"><table class="doc-viewer__doc-table">';

        if ($firstHasText) {
            $markup .= '<thead><tr>';
            foreach ($rows[0]['cells'] as $cell) {
                if (! empty($cell['skip'])) {
                    continue;
                }
                $markup .= '<th'.$this->cellAttributes($cell).'>'.$cell['text'].'</th>';
            }
            $markup .= '</tr></thead>';
            $headerIndex = 1;
        }

        $markup .= '<tbody>';
        for ($index = $headerIndex; $index < count($rows); $index++) {
            $markup .= '<tr>';
            foreach ($rows[$index]['cells'] as $cell) {
                if (! empty($cell['skip'])) {
                    continue;
                }
                $markup .= '<td'.$this->cellAttributes($cell).'>'.$cell['text'].'</td>';
            }
            $markup .= '</tr>';
        }

        return $markup.'</tbody></table></div>';
    }

    /**
     * @param  array{cells: array<int, array<string, mixed>>, width: int}  $row
     * @return array<string, mixed>|null
     */
    private function cellAtColumn(array $row, int $column): ?array
    {
        $index = 0;

        foreach ($row['cells'] as $cell) {
            if ($index === $column) {
                return $cell;
            }
            $index += (int) ($cell['colspan'] ?? 1);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $cell
     */
    private function cellAttributes(array $cell): string
    {
        $attributes = '';

        if ((int) ($cell['colspan'] ?? 1) > 1) {
            $attributes .= ' colspan="'.(int) $cell['colspan'].'"';
        }

        if ((int) ($cell['rowspan'] ?? 1) > 1) {
            $attributes .= ' rowspan="'.(int) $cell['rowspan'].'"';
        }

        return $attributes;
    }

    private function wordValue(DOMElement $node): string
    {
        $val = $node->getAttributeNS(self::WORD_NS, 'val');

        return $val !== '' ? $val : $node->getAttribute('w:val');
    }

    private function sheetHtml(string $path, string $extension): ?string
    {
        $rows = $this->sheets->rows($path, $extension);
        if ($rows === []) {
            return null;
        }

        $truncated = count($rows) > self::MAX_TABLE_ROWS;
        $rows = array_slice($rows, 0, self::MAX_TABLE_ROWS);

        $width = 0;
        foreach ($rows as $row) {
            $width = max($width, is_array($row) ? count($row) : 0);
        }
        if ($width < 1) {
            return null;
        }

        $markup = '<div class="doc-viewer__doc"><div class="doc-viewer__doc-scroll"><table class="doc-viewer__doc-table"><tbody>';

        foreach ($rows as $row) {
            $markup .= '<tr>';
            for ($column = 0; $column < $width; $column++) {
                $value = is_array($row) ? ($row[$column] ?? null) : null;
                $markup .= '<td>'.e(is_scalar($value) ? (string) $value : '', false).'</td>';
            }
            $markup .= '</tr>';
        }

        $markup .= '</tbody></table></div>';

        if ($truncated) {
            $markup .= '<p class="doc-viewer__doc-note">Only the first '.self::MAX_TABLE_ROWS.' rows are previewed. Use the download button for the whole workbook.</p>';
        }

        return $markup.'</div>';
    }
}
