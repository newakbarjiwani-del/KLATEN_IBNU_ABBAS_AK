<?php

namespace App\Services;

use App\Models\Santri;
use Exception;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class RaportKesantrianSpreadsheetService
{
    /**
     * Parse uploaded file (XLSX or CSV) and return normalized rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseFile(UploadedFile|string $file): array
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

        return $this->normalizeRows($rawRows);
    }

    /**
     * Enrich parsed rows with student names from scctcust table.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function enrichWithSantri(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $nisList = array_values(array_filter(array_unique(array_column($rows, 'nis'))));

        $students = Santri::query()
            ->whereIn('NOCUST', $nisList)
            ->get(['NOCUST', 'NMCUST'])
            ->keyBy(fn ($s) => trim((string) $s->NOCUST));

        foreach ($rows as &$row) {
            $nis = trim((string) ($row['nis'] ?? ''));
            $student = $students->get($nis);
            $row['nama'] = $student ? $student->nama() : '';
            $row['santri_ditemukan'] = $student !== null;
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
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeRows(array $rawRows): array
    {
        if (count($rawRows) < 2) {
            return [];
        }

        $headerRow = array_map(fn ($h) => strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $h)), $rawRows[0]);

        $colMap = [];
        $expected = [
            'nis' => ['nis', 'nocust', 'noinduk'],
            'ibadah1' => ['ibadah1', 'ibadaha', 'sholatwajib'],
            'ibadah2' => ['ibadah2', 'ibadahb', 'sholatdhuha'],
            'ibadah3' => ['ibadah3', 'ibadahc', 'sholatlail'],
            'ibadah4' => ['ibadah4', 'ibadahd', 'puasasunnah'],
            'ibadah5' => ['ibadah5', 'ibadahe', 'sedekah'],
            'adab1' => ['adab1', 'adaba', 'menghormati'],
            'adab2' => ['adab2', 'adabb', 'berkatasopan'],
            'adab3' => ['adab3', 'adabc', 'jujur'],
            'adab4' => ['adab4', 'adabd', 'tolongmenolong'],
            'adab5' => ['adab5', 'adabe', 'mengajakkebaikan'],
            'pribadi1' => ['pribadi1', 'kepribadiana', 'kepribadian1', 'kebersihandiri'],
            'pribadi2' => ['pribadi2', 'kepribadianb', 'kepribadian2', 'kebersihanlingkungan'],
            'pribadi3' => ['pribadi3', 'kepribadianc', 'kepribadian3', 'hubungansosial'],
            'pribadi4' => ['pribadi4', 'kepribadiand', 'kepribadian4', 'disiplin'],
            'pribadi5' => ['pribadi5', 'kepribadiane', 'kepribadian5', 'kesehatan'],
            'diri1' => ['diri1', 'pengembangana', 'pengembangan1', 'pengembangandiri'],
            'diri2' => ['diri2', 'pengembanganb', 'pengembangan2', 'aktiforganisasi'],
            'diri3' => ['diri3', 'pengembanganc', 'pengembangan3', 'literasibuku'],
            'catatan' => ['catatan', 'catatanwali', 'keterangan', 'walihujroh'],
        ];

        foreach ($expected as $targetKey => $aliases) {
            foreach ($headerRow as $idx => $cellText) {
                if (in_array($cellText, $aliases, true)) {
                    $colMap[$targetKey] = $idx;
                    break;
                }
            }
        }

        // Positional fallback if headers weren't completely identified
        $positionalOrder = [
            'nis', 'ibadah1', 'ibadah2', 'ibadah3', 'ibadah4', 'ibadah5',
            'adab1', 'adab2', 'adab3', 'adab4', 'adab5',
            'pribadi1', 'pribadi2', 'pribadi3', 'pribadi4', 'pribadi5',
            'diri1', 'diri2', 'diri3', 'catatan',
        ];
        if (! isset($colMap['nis']) && count($headerRow) >= 1) {
            foreach ($positionalOrder as $pIdx => $pKey) {
                if (! isset($colMap[$pKey]) && isset($headerRow[$pIdx])) {
                    $colMap[$pKey] = $pIdx;
                }
            }
        }

        $results = [];
        $dataRows = array_slice($rawRows, 1);

        foreach ($dataRows as $rowIdx => $row) {
            $nis = isset($colMap['nis']) ? ($row[$colMap['nis']] ?? '') : ($row[0] ?? '');
            $nis = trim((string) $nis);

            if ($nis === '') {
                continue;
            }

            $results[] = [
                'baris' => $rowIdx + 2,
                'nis' => $nis,
                'ibadah1' => isset($colMap['ibadah1']) ? ($row[$colMap['ibadah1']] ?? '') : '',
                'ibadah2' => isset($colMap['ibadah2']) ? ($row[$colMap['ibadah2']] ?? '') : '',
                'ibadah3' => isset($colMap['ibadah3']) ? ($row[$colMap['ibadah3']] ?? '') : '',
                'ibadah4' => isset($colMap['ibadah4']) ? ($row[$colMap['ibadah4']] ?? '') : '',
                'ibadah5' => isset($colMap['ibadah5']) ? ($row[$colMap['ibadah5']] ?? '') : '',
                'adab1' => isset($colMap['adab1']) ? ($row[$colMap['adab1']] ?? '') : '',
                'adab2' => isset($colMap['adab2']) ? ($row[$colMap['adab2']] ?? '') : '',
                'adab3' => isset($colMap['adab3']) ? ($row[$colMap['adab3']] ?? '') : '',
                'adab4' => isset($colMap['adab4']) ? ($row[$colMap['adab4']] ?? '') : '',
                'adab5' => isset($colMap['adab5']) ? ($row[$colMap['adab5']] ?? '') : '',
                'pribadi1' => isset($colMap['pribadi1']) ? ($row[$colMap['pribadi1']] ?? '') : '',
                'pribadi2' => isset($colMap['pribadi2']) ? ($row[$colMap['pribadi2']] ?? '') : '',
                'pribadi3' => isset($colMap['pribadi3']) ? ($row[$colMap['pribadi3']] ?? '') : '',
                'pribadi4' => isset($colMap['pribadi4']) ? ($row[$colMap['pribadi4']] ?? '') : '',
                'pribadi5' => isset($colMap['pribadi5']) ? ($row[$colMap['pribadi5']] ?? '') : '',
                'diri1' => isset($colMap['diri1']) ? ($row[$colMap['diri1']] ?? '') : '',
                'diri2' => isset($colMap['diri2']) ? ($row[$colMap['diri2']] ?? '') : '',
                'diri3' => isset($colMap['diri3']) ? ($row[$colMap['diri3']] ?? '') : '',
                'catatan' => isset($colMap['catatan']) ? ($row[$colMap['catatan']] ?? '') : '',
            ];
        }

        return $results;
    }

    /**
     * Generate template XLSX file.
     */
    public function generateXlsxTemplate(string $outputPath): bool
    {
        return $this->createXlsx($outputPath, [$this->templateHeaders(), $this->templateSample()]);
    }

    /**
     * Generate template CSV content.
     */
    public function generateCsvTemplate(): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $this->templateHeaders());
        fputcsv($output, $this->templateSample());
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return (string) $csv;
    }

    /**
     * @return list<string>
     */
    protected function templateHeaders(): array
    {
        return [
            'NIS',
            'Sholat Wajib', 'Sholat Dhuha', 'Sholat Lail', 'Puasa Sunnah', 'Sedekah',
            'Menghormati', 'Berkata Sopan', 'Jujur', 'Tolong Menolong', 'Mengajak Kebaikan',
            'Kebersihan Diri', 'Kebersihan Lingkungan', 'Hubungan Sosial', 'Disiplin', 'Kesehatan',
            'Pengembangan Diri', 'Aktif Organisasi', 'Literasi Buku',
            'Wali Hujroh',
        ];
    }

    /**
     * @return list<string>
     */
    protected function templateSample(): array
    {
        return [
            '999999999',
            'A', 'B', 'C', 'D', 'E',
            'A', 'B', 'C', 'D', 'E',
            'A', 'B', 'C', 'D', 'E',
            'A', 'A', 'B',
            'Coba burhan 12mei26',
        ];
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
