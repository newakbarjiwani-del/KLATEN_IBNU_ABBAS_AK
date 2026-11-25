<?php

namespace Database\Seeders;

use App\Models\ItemRaportKesantrian;
use App\Models\KategoriRaportKesantrian;
use App\Models\NilaiRaportKesantrian;
use App\Models\ReferensiPelanggaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriRaportKesantrianSeeder extends Seeder
{
    /**
     * Struktur awal sama dengan kolom raport kesantrian yang sudah dipakai.
     * Catatan tidak masuk master.
     *
     * @var array<string, list<string>>
     */
    private array $struktur = [
        'Ibadah' => [
            'Sholat Wajib',
            'Sholat Dhuha',
            'Sholat Lail',
            'Puasa Sunnah',
            'Sedekah',
        ],
        'Adab' => [
            'Menghormati',
            'Berkata Sopan',
            'Jujur',
            'Tolong Menolong',
            'Mengajak Kebaikan',
        ],
        'Kepribadian' => [
            'Kebersihan Diri',
            'Kebersihan Lingkungan',
            'Hubungan Sosial',
            'Disiplin',
            'Kesehatan',
        ],
        'Pengembangan Diri' => [
            'Pengembangan Diri',
            'Aktif Organisasi',
            'Literasi Buku',
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            NilaiRaportKesantrian::query()->delete();
            ItemRaportKesantrian::withTrashed()->forceDelete();
            KategoriRaportKesantrian::withTrashed()->forceDelete();

            foreach (ReferensiPelanggaran::UNITS as $unit) {
                $urutKategori = 1;

                foreach ($this->struktur as $namaKategori => $butir) {
                    $kategori = KategoriRaportKesantrian::query()->create([
                        'unit' => $unit,
                        'nama' => $namaKategori,
                        'urut' => $urutKategori,
                    ]);
                    $urutKategori++;

                    foreach ($butir as $index => $namaButir) {
                        $kategori->items()->create([
                            'nama' => $namaButir,
                            'urut' => $index + 1,
                        ]);
                    }
                }
            }
        });
    }
}
