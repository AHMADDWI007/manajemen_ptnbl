<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanDalamProses extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database (plural).
     */
    protected $table = 'bahan_dalam_proses';

    /**
     * Kolom yang boleh diisi melalui API dari aplikasi mobile.
     */
    protected $fillable = [
        'uraian',
        'wip_masuk',
        'wip_keluar',
        'produksi_sir20',
        'rekfif',
        'saldo_akhir',
        'keterangan',
    ];
}
