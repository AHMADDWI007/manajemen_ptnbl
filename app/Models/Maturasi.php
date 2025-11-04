<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maturasi extends Model
{
    use HasFactory;

    // Ini adalah Model untuk TABEL STATE 'maturasi'
    protected $table = 'maturasi';

    protected $fillable = [
        'uraian',
        'stok_akhir',
        'tgl_masuk',
        'umur',
        'asal_bokar',
        'keterangan',
    ];

    protected $casts = [
        'tgl_masuk' => 'datetime',
        'stok_akhir' => 'decimal:2',
        'umur' => 'integer',
    ];

    // Relasi: 1 Master Bak punya BANYAK Log Transaksi
    public function logs() {
        return $this->hasMany(PengolahanMaturasi::class, 'maturasi_id');
    }
}