<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanProses extends Model
{
    use HasFactory;

    /**
     * Nama tabel database.
     */
    protected $table = 'bahan_proses';

    /**
     * Daftar kolom yang boleh diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'tanggal',      // <--- Pastikan ini ada
        'uraian',
        'saldo_awal',   // <--- Pastikan ini ada
        'wip_masuk',
        'wip_keluar',
        'produksi_sir20',
        'rekfif',       // <--- Pastikan ini ada
        'rekfif',
        'saldo_akhir',
        'keterangan',
    ];
}