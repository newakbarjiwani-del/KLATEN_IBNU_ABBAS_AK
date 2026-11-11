<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaportTahfidz extends Model
{
    /**
     * The live table is spelled rapot, and tahfidzh ends with zh.
     */
    protected $table = 'akademik_rapot_tahfidzh';

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
