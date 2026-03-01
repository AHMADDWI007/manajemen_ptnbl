<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanManualSir20 extends Model
{
    use HasFactory;

    protected $table = 'penjualan_manual_sir20';
    protected $primaryKey = 'id_penjualan_manual';

    protected $fillable = [
        'id_penjualan_sir20',
        'tanggal',
        'pallet_manual',
        'berat_manual',
        'status',
    ];

    // 🔥 Tambahkan relasi ke PenjualanSir20
    public function penjualan()
    {
        // Parameter: ModelTujuan, FK_di_tabel_ini, PK_di_tabel_tujuan
        return $this->belongsTo(PenjualanSir20::class, 'id_penjualan_sir20', 'id_penjualan_sir20');
    }
}