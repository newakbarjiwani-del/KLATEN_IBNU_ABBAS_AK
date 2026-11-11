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
                    'label' => 'Data Setoran dan Tahfidz',
                    'summary' => 'Daftar setoran bacaan dan murajaah. Saring menurut tanggal, mode, dan periode.',
                ],
                [
                    'slug' => 'murajaah',
                    'label' => 'Murajaah',
                    'summary' => 'Buka berkas murajaah, periksa isinya, lalu simpan ke periode yang dipilih.',
                ],
                [
                    'slug' => 'setoran',
                    'label' => 'Setoran',
                    'summary' => 'Buka berkas setoran, periksa isinya, lalu simpan ke periode yang dipilih.',
                ],
                [
                    'slug' => 'setoran-lama',
                    'label' => 'Setoran (OLD)',
                    'summary' => 'Arsip setoran lama, termasuk surat, ayat, dan tahun akademik.',
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
