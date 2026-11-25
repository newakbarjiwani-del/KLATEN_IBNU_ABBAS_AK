<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriRaportKesantrian extends Model
{
    use SoftDeletes;

    protected $table = 'akademik_ref_raport_kategori';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'unit',
        'nama',
        'urut',
    ];

    /**
     * @return HasMany<ItemRaportKesantrian, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItemRaportKesantrian::class, 'kategori_id');
    }

    /**
     * @return Collection<int, $this>
     */
    public static function untuk(string $unit): Collection
    {
        return static::query()
            ->where('unit', $unit)
            ->with(['items' => function ($query): void {
                $query->orderBy('urut')->orderBy('id');
            }])
            ->orderBy('urut')
            ->orderBy('id')
            ->get();
    }
}
