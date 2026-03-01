<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanSir20 extends Model
{
    use HasFactory;

    protected $table = 'penjualan_sir20';
    protected $primaryKey = 'id_penjualan_sir20';
    protected $guarded = [];

    protected $fillable = [
        'tanggal', 'uraian', 'no_kontrak', 'no_invoice', 
        'pallet', 'hari_ini', 'harga', 'no_palet_list', 'is_summary'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'sd_bulan_lalu' => 'float', 'bln_ini_lalu' => 'float', 'hari_ini' => 'float',
        'total_bln_ini' => 'float', 'total_sd_hari_ini' => 'float',
    ];
}