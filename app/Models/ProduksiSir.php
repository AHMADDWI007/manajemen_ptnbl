<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSir extends Model
{
    use HasFactory;

    protected $table = 'produksi_sir';
    protected $primaryKey = 'id_produksi_sir';
    protected $guarded = [];
    
    protected $casts = [
        'saldo_awal' => 'float', 'masuk' => 'float', 'total' => 'float',
        'prod_bln_lalu' => 'float', 'prod_sd_hi' => 'float', 'pengiriman' => 'float',
        'saldo_akhir' => 'float', 'kg' => 'float', 'pallet' => 'integer',
        'created_at' => 'datetime',
    ];
}