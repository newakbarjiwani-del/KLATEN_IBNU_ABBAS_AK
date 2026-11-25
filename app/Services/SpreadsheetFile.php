<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class SpreadsheetFile
{
    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function download(string $filename, array $rows, string $sheet = 'Data'): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');

        if ($path === false || ! $this->write($path, $rows, $sheet)) {
            abort(500, 'Berkas Excel tidak dapat dibuat.');
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function write(string $path, array $rows, string $sheet): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $sheet = mb_substr(preg_replace('/[\/\\\?\*\[\]:]/u', '', $sheet) ?? 'Data', 0, 31);
        $sheetData = '';
        $rowNumber = 1;

        foreach ($rows as $row) {
            $sheetData .= '<row r="'.$rowNumber.'">';
            $column = 0;

            foreach ($row as $value) {
                $letters = $this->columnLetter($column);
                $text = htmlspecialchars($this->plain((string) $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $sheetData .= '<c r="'.$letters.$rowNumber.'" t="inlineStr"><is><t>'.$text.'</t></is></c>';
                $column++;
            }

            $sheetData .= '</row>';
            $rowNumber++;
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.htmlspecialchars($sheet, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetData.'</sheetData></worksheet>');

        return $zip->close();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function read(string $path, string $extension = ''): array
    {
        $extension = strtolower($extension !== '' ? $extension : pathinfo($path, PATHINFO_EXTENSION));

        return $extension === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $line = array_map(fn (mixed $value): string => trim((string) $value), $data);

            if (count(array_filter($line, fn (string $value): bool => $value !== '')) > 0) {
                $rows[] = $line;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [];
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml !== false) {
            $xml = @simplexml_load_string($sharedXml);

            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $item) {
                    if (isset($item->t)) {
                        $shared[] = (string) $item->t;
                    } elseif (isset($item->r)) {
                        $text = '';
                        foreach ($item->r as $part) {
                            $text .= (string) $part->t;
                        }
                        $shared[] = $text;
                    } else {
                        $shared[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $xml = @simplexml_load_string($sheetXml);

        if (! $xml || ! isset($xml->sheetData)) {
            return [];
        }

        $rows = [];

        foreach ($xml->sheetData->row as $row) {
            $cells = [];

            foreach ($row->c as $cell) {
                $letters = preg_replace('/[0-9]/', '', (string) $cell['r']) ?? '';
                $index = $this->columnIndex($letters);
                $type = (string) $cell['t'];
                $value = '';

                if ($type === 's') {
                    $value = $shared[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } elseif (isset($cell->v)) {
                    $value = (string) $cell->v;
                }

                $cells[$index] = trim($value);
            }

            if ($cells === []) {
                continue;
            }

            $line = [];
            $last = max(array_keys($cells));

            for ($column = 0; $column <= $last; $column++) {
                $line[] = $cells[$column] ?? '';
            }

            $rows[] = $line;
        }

        return $rows;
    }

    private function columnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function plain(string $value): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';
    }

    private function columnLetter(int $index): string
    {
        $letters = '';
        $index++;

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $index = intdiv($index - $remainder, 26);
        }

        return $letters;
    }
}
