<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferensiPelanggaran extends Model
{
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
    ];
}
