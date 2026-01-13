<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokHarian extends Model
{
    protected $table = 'stok_harian';

    protected $fillable = [
        'tanggal',
        'stok_awal',
        'bokar_masuk_hi',
        'bokar_masuk_sdhi',
        'bokar_diolah_hi',
        'bokar_diolah_sdhi',
        'stok_akhir',
        'detail_masuk_hi',
    ];

    protected $casts = [
        'detail_masuk_hi' => 'array', // biar otomatis decode JSON
    ];
}
