<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferensiPelanggaran extends Model
{
    use SoftDeletes;

    public const UNITS = [
        'KUTTAB',
        'KMI',
        'SMP',
        'SMA',
        "MA'HAD ALY",
    ];

    protected $table = 'akademik_ref_langgar';

    protected $primaryKey = 'urut';

    public $incrementing = true;

    public $timestamps = false;

    /**
     * The live column is jenis_pelangaran, without the second g.
     *
     * @var list<string>
     */
    protected $fillable = [
        'jenis_pelangaran',
        'skor',
        'keterangan',
        'unit',
    ];

    public static function canonicalUnit(string $value): ?string
    {
        $normalized = strtoupper(trim($value));
        $normalized = str_replace(['’', '`'], "'", $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $aliases = [
            'KUTTAB' => 'KUTTAB',
            'KMI' => 'KMI',
            'SMP' => 'SMP',
            'SMA' => 'SMA',
            "MA'HAD ALY" => "MA'HAD ALY",
            'MAHAD ALY' => "MA'HAD ALY",
            'MAHADALY' => "MA'HAD ALY",
        ];

        return $aliases[$normalized] ?? null;
    }
}
