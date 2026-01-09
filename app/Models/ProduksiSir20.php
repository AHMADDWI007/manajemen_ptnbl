<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiSir20 extends Model
{
    use HasFactory;

    protected $table = 'produksi_sir20';
    protected $primaryKey = 'id_produksi_sir20';

    protected $fillable = [
        'tanggal_produksi', 'shift_kerja', 'jam_start_dryer', 'jumlah_trolly_masuk',
        'jumlah_trolly_keluar', 'jam_stop_dryer', 'jumlah_jam_dryer', 'jumlah_bales_dipress',
        'kg_yang_dipress', 'capacity_per_jam', 'jam_kerja', 'produktivitas', 'kg_cake',
        'bales_terkontaminasi', 'berat_kontaminan', 'jam_operasional_genset', 'pemakaian_listrik_pln',
        'jumlah_pallet', 'total_nomor', 'mc_val', 'nomor_start', 'nomor_end', 'total_nomor_akhir', 'petugas',
    ];

    public function remahan()
    {
        return $this->hasMany(RemahanSir20::class, 'id_produksi_sir20');
    }

    public function aktualTemperature()
    {
        return $this->hasMany(AktualTemperatureSir20::class, 'id_produksi_sir20');
    }

    public function bahanBakar()
    {
        return $this->hasMany(BahanBakarSir20::class, 'id_produksi_sir20');
    }
}