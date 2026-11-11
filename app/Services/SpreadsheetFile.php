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
