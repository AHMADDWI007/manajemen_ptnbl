<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSir extends Model
{
    use HasFactory;

    // 1. KUNCI UTAMA: Definisikan nama tabel secara manual
    // Agar tidak dianggap 'produksi_sirs' oleh Laravel
    protected $table = 'produksi_sir';

    // 2. Izinkan semua kolom diisi (Mass Assignment)
    // Ini lebih praktis daripada menulis fillable satu per satu
    protected $guarded = [];
    
    // Casting agar angka desimal tidak jadi string saat diambil
    protected $casts = [
        'saldo_awal' => 'float',
        'masuk' => 'float',
        'total' => 'float',
        'prod_bln_lalu' => 'float',
        'prod_sd_hi' => 'float',
        'pengiriman' => 'float',
        'saldo_akhir' => 'float',
        'kg' => 'float',
        'pallet' => 'integer',
        'created_at' => 'datetime',
    ];
}