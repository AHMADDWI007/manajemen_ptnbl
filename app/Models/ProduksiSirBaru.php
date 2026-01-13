<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSirBaru extends Model
{
    use HasFactory;

    protected $guarded = ['id']; // Membiarkan semua kolom bisa diisi kecuali ID
    
    // Atau jika ingin mendefinisikan fillable secara manual (opsional, tapi $guarded lebih cepat)
    // protected $table = 'produksi_sir_barus';
}