<?php

namespace App\Kesantrian;

class KesantrianMenu
{
    /**
     * @return array<string, array<int, array{slug: string, label: string, summary: string}>>
     */
    public static function groups(): array
    {
        return [
            'Master' => [
                [
                    'slug' => 'tahun-akademik',
                    'label' => 'Master Tahun Akademik',
                    'summary' => 'Kelola tahun ajaran dan semester. Nomor urut berjalan dari yang terkecil.',
                ],
                [
                    'slug' => 'master-pelanggaran',
                    'label' => 'Master Pelanggaran',
                    'summary' => 'Referensi nama pelanggaran, skor, dan keterangan. Nomor urut berjalan dari yang terkecil.',
                ],
            ],
            'Pelanggaran' => [
                [
                    'slug' => 'input-pelanggaran',
                    'label' => 'Input Pelanggaran',
                    'summary' => 'Catat pelanggaran atau remisi santri. Skor pelanggaran terisi otomatis dari master.',
                ],
                [
                    'slug' => 'data-pelanggaran',
                    'label' => 'Data Pelanggaran',
                    'summary' => 'Telusuri catatan pelanggaran santri yang sudah masuk.',
                ],
            ],
            'Raport Kesantrian' => [
                [
                    'slug' => 'input-raport-kesantrian',
                    'label' => 'Input Raport Kesantrian',
                    'summary' => 'Isi nilai sikap dan catatan kesantrian untuk raport santri.',
                ],
                [
                    'slug' => 'data-raport-kesantrian',
                    'label' => 'Data Raport Kesantrian',
                    'summary' => 'Lihat raport kesantrian yang sudah diinput.',
                ],
            ],
            'Raport' => [
                [
                    'slug' => 'upload-raport',
                    'label' => 'Upload Raport',
                    'summary' => 'Unggah PDF raport tahfidz. Jenis ujian dipilih Mid atau Akhir.',
                ],
                [
                    'slug' => 'data-raport',
                    'label' => 'Data Raport',
                    'summary' => 'Arsip PDF raport tahfidz menurut tahun, kelas, dan santri.',
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
