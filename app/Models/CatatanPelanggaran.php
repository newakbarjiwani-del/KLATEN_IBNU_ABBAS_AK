<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanPelanggaran extends Model
{
    protected $table = 'akademik_pelanggaran';

    protected $primaryKey = 'urut';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'NOCUST',
        'tgl_kejadian',
        'skor_debet',
        'skor_kredit',
        'id_pelanggaran',
        'id_remisi',
        'keterangan',
        'thn_aka',
    ];
}
