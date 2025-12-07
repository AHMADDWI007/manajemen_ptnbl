<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanSir20 extends Model
{
    use HasFactory;

    protected $table = 'penjualan_sir20';

    protected $fillable = [
        'tanggal', // ✅ WAJIB ADA
        'uraian',
        'sd_bulan_lalu',
        'penjualan_bulan_ini_yg_lalu',
        'penjualan_bulan_ini_hari_ini', // Mapping input 'hari_ini'
        'total_bulan_ini',
        'total_penjualan_sd_hari_ini',
        'keterangan',
    ];
}