<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanProses extends Model
{
    use HasFactory;

    protected $table = 'bahan_proses';
    protected $primaryKey = 'id_bahan_proses';

    protected $fillable = [
        'tanggal', 'uraian', 'saldo_awal', 'wip_masuk', 'wip_keluar', 'produksi_sir20', 'rekfif', 'saldo_akhir', 'keterangan',
    ];
    protected $casts = [
        'tanggal' => 'date',
    ];
}