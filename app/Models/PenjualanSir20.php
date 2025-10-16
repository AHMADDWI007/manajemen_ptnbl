<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanSir20 extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     */
    protected $table = 'penjualan_sir20';

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'uraian',
        'sd_bulan_lalu',
        'penjualan_bulan_ini_yg_lalu',
        'penjualan_bulan_ini_hari_ini',
        'total_bulan_ini',
        'total_penjualan_sd_hari_ini',
        'keterangan',
    ];
}
