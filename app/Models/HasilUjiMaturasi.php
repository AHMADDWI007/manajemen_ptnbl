<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne; // <-- TAMBAHKAN INI

class HasilUjiMaturasi extends Model
{
    use HasFactory;
    
    // Pastikan nama tabel benar jika tidak 'hasil_uji_maturasis'
    protected $table = 'hasil_uji_maturasi'; 
    
    // (fillable Anda)
    protected $fillable = ['tanggal', 'no_kamar', 'k3', 'po', 'pa', 'pri'];

    // ==========================================================
    // TAMBAHKAN FUNGSI RELASI INI
    // ==========================================================
    public function maturasi(): HasOne
    {
        // Satu data uji ini dimiliki oleh satu baris maturasi
        return $this->hasOne(Maturasi::class, 'id_hasil_uji_maturasi');
    }
    // ==========================================================
}