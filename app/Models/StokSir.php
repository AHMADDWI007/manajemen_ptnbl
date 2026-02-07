<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokSir extends Model
{
    use HasFactory;

    // Nama Tabel
    protected $table = 'stok_sir';

    // Primary Key
    protected $primaryKey = 'id_stok_sir';

    // Agar bisa mass assignment (create/update banyak kolom sekaligus)
    protected $guarded = [];

    // Relasi ke Model Lokasi
    // Supaya bisa panggil: $stok->lokasi->nama (Misal: "Di Gudang SIR")
    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi', 'id_lokasi');
    }
}