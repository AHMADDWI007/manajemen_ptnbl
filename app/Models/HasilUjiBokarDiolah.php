<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiBokarDiolah extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan.
     */
    protected $table = 'hasil_uji_bokar_diolah';

    /**
     * Field yang boleh diisi.
     */
    protected $fillable = [
        'tanggal',
        'bak_maturasi',
        'k3',
        'jenis',
        'netto_basah',
        'netto_kering',
    ];
}