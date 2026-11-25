<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiRaportKesantrian extends Model
{
    protected $table = 'akademik_raport_kesantrian_nilai';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'raport_urut',
        'item_id',
        'nilai',
    ];
}
