<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon; // <-- Import Carbon

class PengolahanMaturasi extends Model
{
    // GANTI NAMA TABEL DARI 'maturasis'
    protected $table = 'pengolahan_maturasi'; 

    protected $fillable = [
        'maturasi_id',
        'tanggal_input', 
        'stok_awal', 
        'diolah', 
        'mutasi', 
        'masuk_hi', 
        'stok_akhir', 
        'keterangan',
        'tgl_masuk_log', // <-- 1. TAMBAHKAN INI
        'umur_log',      // <-- 2. TAMBAHKAN INI
    ];

    protected $casts = [
        'tanggal_input' => 'datetime', // <-- 3. TAMBAHKAN INI
        'tgl_masuk_log' => 'datetime', // <-- 4. TAMBAHKAN INI
    ];

    // Relasi: 1 Log Transaksi milik 1 Master Bak
    public function maturasi() {
        return $this->belongsTo(Maturasi::class, 'maturasi_id');
    }
}