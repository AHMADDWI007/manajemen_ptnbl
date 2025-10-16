<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maturasi extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     * Nama 'maturasis' (plural) digunakan untuk mengikuti konvensi Laravel.
     * @var string
     */
    protected $table = 'maturasis';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Kolom-kolom ini cocok dengan yang ada di file view Anda.
     * @var array<int, string>
     */
    protected $fillable = [
        'uraian_proses',
        'kg_kk',
        'tgl',
        'umur',
        'diolah',
        'mutasi',
        'masuk_hi',
        'k3',
        'po',
        'pa',
        'pri',
        'stock_akhir',
        'asal_bokar',
        'keterangan',
    ];
}

