<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSir20 extends Model
{
    use HasFactory;

    protected $table = 'produksi_sir20';
    
    // Izinkan mass assignment untuk semua kolom
    protected $guarded = []; 
}