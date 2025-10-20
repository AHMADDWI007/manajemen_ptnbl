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
        'uraian',
        'wip_masuk',
        'wip_keluar',
        'produksi_sir20',
        'rekfif',
        'saldo_akhir',
        'keterangan',
    ];
}
