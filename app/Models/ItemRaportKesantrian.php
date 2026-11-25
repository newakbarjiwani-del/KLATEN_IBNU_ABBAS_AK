<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemRaportKesantrian extends Model
{
    use SoftDeletes;

    protected $table = 'akademik_ref_raport_item';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kategori_id',
        'nama',
        'urut',
    ];

    /**
     * @return BelongsTo<KategoriRaportKesantrian, $this>
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriRaportKesantrian::class, 'kategori_id');
    }
}
