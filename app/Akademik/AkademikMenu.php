<?php

namespace App\Akademik;

class AkademikMenu
{
    /**
     * @return array<string, array<int, array{slug: string, label: string, summary: string}>>
     */
    public static function groups(): array
    {
        return [
            'Raport' => [
                [
                    'slug' => 'upload-raport',
                    'label' => 'Upload Raport',
                    'summary' => 'Unggah PDF raport akademik. Kelas mengikuti unit, kelas, dan kelompok santri. Jenis ujian dipilih Mid atau Akhir.',
                ],
                [
                    'slug' => 'data-raport',
                    'label' => 'Data Raport',
                    'summary' => 'Arsip PDF raport akademik. Filter kelas menggabungkan unit, kelas, dan kelompok.',
                ],
            ],
        ];
    }

    /**
     * @return array{slug: string, label: string, summary: string, group: string}|null
     */
    public static function find(string $slug): ?array
    {
        foreach (self::groups() as $group => $items) {
            foreach ($items as $item) {
                if ($item['slug'] === $slug) {
                    return $item + ['group' => $group];
                }
            }
        }

        return null;
    }
}
