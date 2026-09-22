<?php

namespace Webkul\Lead\Services;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIO;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory as WordIO;
use Smalot\PdfParser\Parser;

class DocumentExtractionService
{
    public function extract(string $path, string $extension): array
    {
        $absolutePath = Storage::disk(config('document_imports.disk'))->path($path);
        $extension = strtolower($extension);

        return match ($extension) {
            'pdf'                => $this->extractPdf($absolutePath),
            'docx'               => $this->extractWord($absolutePath),
            'xlsx', 'xls', 'csv' => $this->extractSpreadsheet($absolutePath, $extension),
            default              => throw new InvalidArgumentException(trans('admin::app.service-errors.unsupported-document')),
        };
    }

    private function extractPdf(string $path): array
    {
        $text = $this->normalize((new Parser)->parseFile($path)->getText());

        return ['type' => 'pdf', 'text' => $text, 'tables' => [], 'metadata' => [
            'possibly_scanned' => mb_strlen($text) < 40,
            'warning'          => mb_strlen($text) < 40 ? trans('admin::app.service-errors.scanned-pdf') : null,
        ]];
    }

    private function extractWord(string $path): array
    {
        $document = WordIO::load($path);
        $lines = [];
        $tables = [];

        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->readWordElement($element, $lines, $tables);
            }
        }

        return ['type' => 'docx', 'text' => $this->normalize(implode("\n", $lines)), 'tables' => $tables, 'metadata' => []];
    }

    private function readWordElement(object $element, array &$lines, array &$tables): void
    {
        if (method_exists($element, 'getText')) {
            $text = $element->getText();
            if (is_string($text) && trim($text) !== '') {
                $lines[] = $text;
            }
        }

        if ($element instanceof TextRun || method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $this->readWordElement($child, $lines, $tables);
            }
        }

        if (method_exists($element, 'getRows')) {
            $table = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cellLines = [];
                    foreach ($cell->getElements() as $child) {
                        $this->readWordElement($child, $cellLines, $tables);
                    }
                    $cells[] = $this->normalize(implode(' ', $cellLines));
                }
                $table[] = $cells;
                $lines[] = implode(' | ', $cells);
            }
            $tables[] = ['rows' => $table];
        }
    }

    private function extractSpreadsheet(string $path, string $extension): array
    {
        $reader = SpreadsheetIO::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        $max = config('document_imports.max_rows');
        $tables = [];
        $total = 0;

        foreach ($book->getWorksheetIterator() as $sheet) {
            $rows = [];
            foreach ($sheet->toArray(null, true, true, false) as $row) {
                if (! array_filter($row, fn ($value) => $value !== null && $value !== '')) {
                    continue;
                }
                if ($total >= $max + 1) {
                    break 2;
                }
                $rows[] = array_map(fn ($value) => is_scalar($value) ? (string) $value : '', $row);
                $total++;
            }
            $tables[] = ['sheet' => $sheet->getTitle(), 'headers' => $rows[0] ?? [], 'rows' => array_slice($rows, 1)];
        }

        $dataRows = collect($tables)->sum(fn ($table) => count($table['rows']));
        if ($dataRows > $max) {
            throw new InvalidArgumentException(trans('admin::app.service-errors.spreadsheet-limit', ['count' => $max]));
        }
        if ($dataRows === 0) {
            throw new InvalidArgumentException(trans('admin::app.service-errors.spreadsheet-empty'));
        }

        return ['type' => $extension, 'text' => '', 'tables' => $tables, 'metadata' => ['mode_hint' => $dataRows > 1 ? 'bulk' : 'single', 'row_count' => $dataRows]];
    }

    private function normalize(string $text): string
    {
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\R{3,}/u', "\n\n", $text) ?? $text);
    }
}
