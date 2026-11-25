<?php

namespace App\Kesantrian;

use App\Models\ReferensiPelanggaran;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UnitRaport
{
    public static function terkunci(): ?string
    {
        return ReferensiPelanggaran::canonicalUnit((string) auth()->user()?->unit);
    }

    public static function dipilih(?string $requested = null): string
    {
        $locked = self::terkunci();

        if ($locked !== null) {
            return $locked;
        }

        return ReferensiPelanggaran::canonicalUnit((string) $requested)
            ?? ReferensiPelanggaran::UNITS[0];
    }

    /**
     * @return list<string>
     */
    public static function kode(string $unit): array
    {
        if ($unit === "MA'HAD ALY") {
            return ["MA'HAD ALY", 'MAHAD ALY', 'MAHADALY'];
        }

        return [$unit];
    }

    public static function batasi(Builder $query, string $column, string $unit): Builder
    {
        return $query->whereIn(DB::raw('UPPER(TRIM('.$column.'))'), self::kode($unit));
    }
}
