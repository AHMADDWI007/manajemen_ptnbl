<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSir extends Model
{
    use HasFactory;

    protected $fillable = [
        'uraian',
        'saldo_awal', 'masuk', 'prod_bln_lalu', 'prod_sd_hi',
        'pengiriman', 'saldo_akhir', 'ptnb', 'total_i_sd_iv',
        'kg', 'pallet',
        'keterangan',
        'created_at', 'updated_at'
    ];
}