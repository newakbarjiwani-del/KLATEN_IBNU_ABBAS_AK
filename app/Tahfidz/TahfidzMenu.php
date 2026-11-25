<?php

namespace App\Tahfidz;

class TahfidzMenu
{
    /**
     * @return array<string, array<int, array{slug: string, label: string, summary: string}>>
     */
    public static function groups(): array
    {
        return [
            'Tahfidz' => [
                [
                    'slug' => 'data-setoran',
                    'label' => 'Data Tahfidz',
                    'summary' => 'Daftar unggahan tahfidz. Saring menurut tanggal, bulan, NIS, atau nama.',
                ],
                [
                    'slug' => 'upload-tahfidz',
                    'label' => 'Upload Tahfidz',
                    'summary' => 'Unggah murojaah dan setoran dalam satu berkas, lalu simpan ke bulan yang dipilih.',
                ],
                [
                    'slug' => 'setoran-lama',
                    'label' => 'Setoran (OLD)',
                    'summary' => 'Arsip setoran lama, termasuk surat, ayat, dan tahun akademik.',
                ],
                [
                    'slug' => 'laporan-capaian',
                    'label' => 'Laporan Capaian',
                    'summary' => 'Lihat capaian satu bulan atau beberapa bulan sekaligus, lalu cetak PDF.',
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
