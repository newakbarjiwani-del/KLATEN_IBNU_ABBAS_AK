<?php

namespace App\Services;

use App\Models\ReferensiPelanggaran;
use App\Models\Santri;
use Exception;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class RaportKesantrianSpreadsheetService
{
    /**
     * Parse uploaded file (XLSX or CSV) and return normalized rows.
     *
     * @param  list<array{id: int, nama: string}>  $items
     * @return array<int, array<string, mixed>>
     */
    public function parseFile(UploadedFile|string $file, array $items): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $rawRows = match ($extension) {
            'csv', 'txt' => $this->parseCsv($filePath),
            default => $this->parseXlsx($filePath),
        };

        if (empty($rawRows)) {
            return [];
        }

        return $this->normalizeRows($rawRows, $items);
    }

    /**
     * Enrich parsed rows with student names from scctcust table.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function enrichWithSantri(array $rows, string $unit): array
    {
        if (empty($rows)) {
            return [];
        }

        $nisList = array_values(array_filter(array_unique(array_column($rows, 'nis'))));

        $students = Santri::query()
            ->whereIn('NOCUST', $nisList)
            ->get(['NOCUST', 'NMCUST', 'CODE02'])
            ->keyBy(fn ($s) => trim((string) $s->NOCUST));

        foreach ($rows as &$row) {
            $nis = trim((string) ($row['nis'] ?? ''));
            $student = $students->get($nis);
            $row['nama'] = $student ? $student->nama() : '';
            $row['santri_ditemukan'] = $student !== null;
            $row['unit_sesuai'] = $student !== null
                && ReferensiPelanggaran::canonicalUnit((string) $student->CODE02) === $unit;
        }
        unset($row);

        return $rows;
    }

    /**
     * Parse XLSX workbook.
     *
     * @return array<int, array<int, string>>
     */
    protected function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new Exception('Gagal membuka berkas Excel (.xlsx). Pastikan berkas tidak rusak atau terproteksi kata sandi.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#i', (string) $stat['name'])) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            throw new Exception('Worksheet tidak ditemukan di dalam berkas Excel.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if (! $xml || ! isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowNum = (int) $row['r'];
            $rowData = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $colLetters = preg_replace('/[0-9]/', '', $ref);
                $colIdx = $this->colLetterToIndex($colLetters);

                $type = (string) $cell['t'];
                $val = '';
                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) $cell->is->t;
                } elseif (isset($cell->v)) {
                    $val = (string) $cell->v;
                }

                $rowData[$colIdx] = trim((string) $val);
            }

            if (! empty($rowData)) {
                $maxCol = max(array_keys($rowData));
                $normalizedRow = [];
                for ($c = 0; $c <= $maxCol; $c++) {
                    $normalizedRow[] = $rowData[$c] ?? '';
                }
                $rows[$rowNum] = $normalizedRow;
            }
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * Parse CSV file with delimiter detection.
     *
     * @return array<int, array<int, string>>
     */
    protected function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new Exception('Gagal membaca berkas CSV.');
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = ',';
        if ($firstLine !== false) {
            $commaCount = substr_count($firstLine, ',');
            $semicolonCount = substr_count($firstLine, ';');
            $tabCount = substr_count($firstLine, "\t");

            if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
                $delimiter = ';';
            } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                $delimiter = "\t";
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Strip potential UTF-8 BOM on first cell of first row
            if (empty($rows) && isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
            }
            $cleanRow = array_map(fn ($val) => trim((string) $val), $data);
            if (count(array_filter($cleanRow, fn ($v) => $v !== '')) > 0) {
                $rows[] = $cleanRow;
            }
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Normalize raw matrix into structured data array.
     *
     * @param  array<int, array<int, string>>  $rawRows
     * @param  list<array{id: int, nama: string}>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRows(array $rawRows, array $items): array
    {
        if (count($rawRows) < 2 || $items === []) {
            return [];
        }

        $headerRow = array_map(fn ($header) => $this->headerKey((string) $header), $rawRows[0]);
        $itemColumns = [];
        $used = [];

        foreach ($headerRow as $index => $cell) {
            if (in_array($cell, ['nis', 'nocust', 'noinduk'], true)) {
                $used['nis'] = $index;
                break;
            }
        }

        foreach ($items as $item) {
            $key = $this->headerKey($item['nama']);
            foreach ($headerRow as $index => $cell) {
                if ($cell === $key && ! in_array($index, $used, true)) {
                    $itemColumns[$item['id']] = $index;
                    $used[$item['id']] = $index;
                    break;
                }
            }
        }

        foreach ($headerRow as $index => $cell) {
            if (in_array($cell, ['catatan', 'catatanwali', 'keterangan', 'walihujroh'], true)) {
                $used['catatan'] = $index;
                break;
            }
        }

        if (! isset($used['nis'])) {
            $used['nis'] = 0;
            $cursor = 1;
            foreach ($items as $item) {
                if (! isset($itemColumns[$item['id']]) && isset($headerRow[$cursor])) {
                    $itemColumns[$item['id']] = $cursor;
                }
                $cursor++;
            }
            if (! isset($used['catatan']) && isset($headerRow[$cursor])) {
                $used['catatan'] = $cursor;
            }
        }

        $results = [];

        foreach (array_slice($rawRows, 1) as $rowIndex => $row) {
            $nis = trim((string) ($row[$used['nis']] ?? ''));
            if ($nis === '') {
                continue;
            }

            $nilai = [];
            foreach ($items as $item) {
                $column = $itemColumns[$item['id']] ?? null;
                $nilai[$item['id']] = $column === null ? '' : trim((string) ($row[$column] ?? ''));
            }

            $results[] = [
                'baris' => $rowIndex + 2,
                'nis' => $nis,
                'nilai' => $nilai,
                'catatan' => isset($used['catatan']) ? trim((string) ($row[$used['catatan']] ?? '')) : '',
            ];
        }

        return $results;
    }

    /**
     * @param  list<array{id: int, nama: string}>  $items
     */
    public function generateXlsxTemplate(string $outputPath, array $items): bool
    {
        return $this->createXlsx($outputPath, [$this->templateHeaders($items)]);
    }

    /**
     * @param  list<array{id: int, nama: string}>  $items
     */
    public function generateCsvTemplate(array $items): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $this->templateHeaders($items));
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return (string) $csv;
    }

    /**
     * @param  list<array{id: int, nama: string}>  $items
     * @return list<string>
     */
    protected function templateHeaders(array $items): array
    {
        return [
            'NIS',
            ...array_map(fn (array $item): string => $item['nama'], $items),
            'Catatan',
        ];
    }

    protected function headerKey(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $value));
    }

    /**
     * Create clean XLSX spreadsheet.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function createXlsx(string $filePath, array $rows): bool
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'.
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'.
            '<Default Extension="xml" ContentType="application/xml"/>'.
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'.
            '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'.
            '</Relationships>';

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'.
            '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'.
            '<sheets><sheet name="RaportKesantrian" sheetId="1" r:id="rId1"/></sheets>'.
            '</workbook>';

        $sheetData = '';
        $rowNum = 1;
        foreach ($rows as $row) {
            $sheetData .= '<row r="'.$rowNum.'">';
            $colIdx = 0;
            foreach ($row as $cellVal) {
                $colLetters = $this->indexToColLetter($colIdx);
                $escaped = htmlspecialchars((string) $cellVal, ENT_XML1, 'UTF-8');
                $sheetData .= '<c r="'.$colLetters.$rowNum.'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
                $colIdx++;
            }
            $sheetData .= '</row>';
            $rowNum++;
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.
            '<sheetData>'.$sheetData.'</sheetData>'.
            '</worksheet>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        return $zip->close();
    }

    protected function colLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    protected function indexToColLetter(int $index): string
    {
        $letters = '';
        $index++;
        while ($index > 0) {
            $rem = ($index - 1) % 26;
            $letters = chr(65 + $rem).$letters;
            $index = intdiv($index - $rem, 26);
        }

        return $letters;
    }
}
