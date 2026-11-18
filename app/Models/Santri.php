<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Santri extends Model
{
    public const PEMISAH_KELAS = '|||';

    protected $table = 'scctcust';

    protected $primaryKey = 'CUSTID';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'NOCUST',
        'NMCUST',
        'DESC02',
        'CODE02',
        'DESC03',
    ];

    public function nama(): string
    {
        return trim((string) $this->NMCUST);
    }

    public static function nisDariTeks(string $teks): ?string
    {
        $teks = trim($teks);

        if ($teks === '') {
            return null;
        }

        $byNis = static::query()->where('NOCUST', $teks)->first();

        if ($byNis !== null) {
            return trim((string) $byNis->NOCUST);
        }

        $byName = static::query()
            ->whereRaw('TRIM(NMCUST) = ?', [$teks])
            ->limit(2)
            ->get();

        if ($byName->count() !== 1) {
            return null;
        }

        return trim((string) $byName->first()->NOCUST);
    }

    /**
     * @return Collection<int, string>
     */
    public static function daftarKelas(): Collection
    {
        $cached = Cache::get('scctcust.kelas');

        if ($cached instanceof Collection) {
            return $cached;
        }

        if (is_array($cached)) {
            return collect($cached);
        }

        Cache::forget('scctcust.kelas');

        /** @var array<int, string> $classes */
        $classes = static::query()
            ->whereNotNull('DESC02')
            ->where('DESC02', '!=', '')
            ->distinct()
            ->orderBy('DESC02')
            ->pluck('DESC02')
            ->all();

        Cache::put('scctcust.kelas', $classes, now()->addMinutes(10));

        return collect($classes);
    }

    /**
     * @return Collection<int, string>
     */
    public static function daftarKelompok(): Collection
    {
        $cached = Cache::get('scctcust.kelompok');

        if ($cached instanceof Collection) {
            return $cached;
        }

        if (is_array($cached)) {
            return collect($cached);
        }

        Cache::forget('scctcust.kelompok');

        /** @var array<int, string> $groups */
        $groups = static::query()
            ->whereNotNull('DESC03')
            ->where('DESC03', '!=', '')
            ->distinct()
            ->orderBy('DESC03')
            ->pluck('DESC03')
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        Cache::put('scctcust.kelompok', $groups, now()->addMinutes(10));

        return collect($groups);
    }

    /**
     * Gabungan unit, kelas, dan kelompok: CODE02, DESC02, DESC03.
     *
     * @return Collection<int, array{unit: string, kelas: string, kelompok: string, kunci: string, label: string}>
     */
    public static function daftarUnitKelas(): Collection
    {
        $cached = Cache::get('scctcust.unit-kelas');

        if ($cached instanceof Collection) {
            return $cached;
        }

        if (is_array($cached)) {
            return collect($cached);
        }

        Cache::forget('scctcust.unit-kelas');

        $rows = static::query()
            ->selectRaw('TRIM(CODE02) as unit, TRIM(DESC02) as kelas, TRIM(DESC03) as kelompok')
            ->groupByRaw('TRIM(CODE02), TRIM(DESC02), TRIM(DESC03)')
            ->orderByRaw('TRIM(CODE02), TRIM(DESC02), TRIM(DESC03)')
            ->get();

        /** @var array<int, array{unit: string, kelas: string, kelompok: string, kunci: string, label: string}> $classes */
        $classes = $rows
            ->map(function (self $row): array {
                $unit = trim((string) $row->getAttribute('unit'));
                $kelas = trim((string) $row->getAttribute('kelas'));
                $kelompok = trim((string) $row->getAttribute('kelompok'));

                return [
                    'unit' => $unit,
                    'kelas' => $kelas,
                    'kelompok' => $kelompok,
                    'kunci' => self::kunciUnitKelas($unit, $kelas, $kelompok),
                    'label' => self::labelUnitKelas($unit, $kelas, $kelompok),
                ];
            })
            ->filter(fn (array $row): bool => $row['label'] !== '')
            ->unique('kunci')
            ->values()
            ->all();

        Cache::put('scctcust.unit-kelas', $classes, now()->addMinutes(10));

        return collect($classes);
    }

    public static function kunciUnitKelas(string $unit, string $kelas, string $kelompok): string
    {
        return $unit.self::PEMISAH_KELAS.$kelas.self::PEMISAH_KELAS.$kelompok;
    }

    public static function labelUnitKelas(string $unit, string $kelas, string $kelompok): string
    {
        return collect([$unit, $kelas, $kelompok])
            ->filter(fn (string $value): bool => $value !== '')
            ->implode(' · ');
    }

    /**
     * @return array{unit: string, kelas: string, kelompok: string}|null
     */
    public static function pecahKunciKelas(string $kunci): ?array
    {
        $parts = explode(self::PEMISAH_KELAS, $kunci, 3);

        if (count($parts) !== 3) {
            return null;
        }

        return [
            'unit' => $parts[0],
            'kelas' => $parts[1],
            'kelompok' => $parts[2],
        ];
    }
}
