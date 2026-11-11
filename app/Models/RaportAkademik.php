<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaportAkademik extends Model
{
    /**
     * The live table is spelled rapot, without the second r.
     */
    protected $table = 'akademik_rapot';

    protected $primaryKey = 'urut';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'NOCUST',
        'THN_AKA',
        'FILE_RAPOT',
        'UJIAN',
    ];
}
