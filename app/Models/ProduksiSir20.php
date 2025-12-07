<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSir20 extends Model
{
    use HasFactory;

    // Nama tabel sesuai permintaan
    protected $table = 'produksi_sir20';

    // Izinkan semua kolom diisi secara massal
    protected $guarded = [];
}