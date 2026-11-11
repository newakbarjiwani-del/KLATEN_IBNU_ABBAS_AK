<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaportKesantrian extends Model
{
    protected $table = 'akademik_raport_kesantrian';

    protected $primaryKey = 'urut';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'NOCUST',
        'tgl',
        'periode',
        'Catatan',
        'IbadahA',
        'IbadahB',
        'IbadahC',
        'IbadahD',
        'IbadahE',
        'AdabA',
        'AdabB',
        'AdabC',
        'AdabD',
        'AdabE',
        'KepribadianA',
        'KepribadianB',
        'KepribadianC',
        'KepribadianD',
        'KepribadianE',
        'PengembanganA',
        'PengembanganB',
        'PengembanganC',
        'Disiplin',
        'Kerapian',
        'Leadership',
        'Sosial',
    ];

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'NOCUST', 'NOCUST');
    }

    public function namaSantri(): string
    {
        return $this->santri ? $this->santri->nama() : '';
    }
}
