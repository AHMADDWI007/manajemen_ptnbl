<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSir20 extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database (plural).
     */
    protected $table = 'produksi_sir20';

    /**
     * Kolom yang boleh diisi melalui API dari aplikasi mobile.
     */
    protected $fillable = [
        'uraian',
        'saldo_awal',
        'masuk',
        'total',
        'produksi_bulan_lalu',
        'produksi_sd_hi',
        'pengiriman',
        'saldo_akhir',
        'pt_nb',
        'total_100_persen',
    ];
}
