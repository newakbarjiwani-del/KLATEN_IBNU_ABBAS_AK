<?php

namespace App\Tahfidz;

use RuntimeException;
use ZipArchive;

class HafalanSheet
{
    /**
     * @return array<int, array<string, string>>
     */
    public function read(string $path, string $kind): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $matrix = $extension === 'xlsx' ? $this->xlsx($path) : $this->csv($path);

        return $this->map($matrix, $kind);
    }

    /**
     * @param  array<int, array<int, string>>  $matrix
     * @return array<int, array<string, string>>
     */
    private function map(array $matrix, string $kind): array
    {
        if ($matrix === []) {
            return [];
        }

        $fields = [
            'nis', 'juzzdihapal', 'juzzposisi', 'juzztotal', 'ziadah', 'murojaah',
            'tuhfah', 'jazari', 'statusujian', 'prestasi', 'jumlahpertemuan', 'totalkehadiran',
        ];
        $aliases = $this->aliases();
        $header = array_map(fn (string $cell): string => $this->normalize($cell), $matrix[0]);
        $start = 0;
        $columns = [];

        foreach ($header as $index => $name) {
            foreach ($aliases as $field => $names) {
                if (in_array($name, $names, true)) {
                    $columns[$field] = $index;
                }
            }
        }

        if (isset($columns['nis'])) {
            $start = 1;
        } else {
            foreach ($fields as $index => $field) {
                $columns[$field] = $index;
            }
        }

        $rows = [];

        foreach (array_slice($matrix, $start) as $line) {
            $row = [];

            foreach ($fields as $field) {
                $index = $columns[$field] ?? null;
                $row[$field] = $index === null ? '' : trim((string) ($line[$index] ?? ''));
            }

            if ($row['nis'] === '') {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function aliases(): array
    {
        return [
            'nis' => ['nis', 'nocust'],
            'juzzdihapal' => ['juzdihapal', 'juzzdihapal', 'juzzdihafal', 'juzdihafal'],
            'juzzposisi' => ['juzposisi', 'juzzposisi'],
            'juzztotal' => ['juztotal', 'juzztotal', 'totaljuz', 'totaljuzz'],
            'ziadah' => ['ziadah', 'ziyadah'],
            'murojaah' => ['murojaah', 'murajaah'],
            'tuhfah' => ['tuhfah'],
            'jazari' => ['jazari'],
            'statusujian' => ['statusujian', 'lulus', 'statuslulus'],
            'prestasi' => ['prestasi', 'statusprestasi'],
            'jumlahpertemuan' => ['jumlahpertemuan', 'pertemuan'],
            'totalkehadiran' => ['totalkehadiran', 'kehadiran'],
        ];
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z0-9]/', '', $value) ?? '';
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function csv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = ',';

        if (is_string($firstLine)) {
            $counts = [
                ',' => substr_count($firstLine, ','),
                ';' => substr_count($firstLine, ';'),
                "\t" => substr_count($firstLine, "\t"),
            ];
            arsort($counts);
            $delimiter = (string) array_key_first($counts);
        }

        $rows = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($rows === [] && isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]) ?? '';
            }

            $clean = array_map(fn (mixed $value): string => trim((string) $value), $data);

            if (count(array_filter($clean, fn (string $value): bool => $value !== '')) > 0) {
                $rows[] = $clean;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function xlsx(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas Excel tidak dapat dibuka.');
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
            throw new RuntimeException('Lembar Excel tidak ditemukan.');
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
                    $value = (string) $cell->is->t;
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
}
