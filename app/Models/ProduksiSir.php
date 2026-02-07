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
    
    // Agar kolom angka desimal terbaca sebagai float, bukan string
    protected $casts = [
        'kg' => 'float', 
        'pallet' => 'integer',
        'tanggal_produksi' => 'date',
    ];

    // Relasi: Satu Laporan Produksi punya BANYAK Pallet
    public function pallets()
    {
        return $this->hasMany(Pallet::class, 'id_produksi_sir', 'id_produksi_sir');
    }
}