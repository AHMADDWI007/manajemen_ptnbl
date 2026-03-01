<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pallet extends Model
{
    protected $table = 'pallet';
    protected $primaryKey = 'id_pallet';
    
    // 🔥 PERBAIKAN: Masukkan kolom baru ke dalam fillable agar bisa diupdate oleh API
    protected $fillable = [
        'id_produksi_sir',
        'no_pallet',
        'berat',
        'jenis_pallet',
        'tanggal_produksi',
        'tanggal_penjualan'
    ];

    // Gunakan guarded jika ingin lebih fleksibel, tapi fillable lebih aman untuk API
    // protected $guarded = []; 

    public function historyLokasi() {
        return $this->hasMany(LokasiPallet::class, 'id_pallet');
    }

    public function historyMutu() {
        return $this->hasMany(KondisiPallet::class, 'id_pallet');
    }

    public function latestLokasi() {
        return $this->hasOne(LokasiPallet::class, 'id_pallet')->latestOfMany('id_lokasi_pallet');
    }

    public function latestMutu() {
        return $this->hasOne(KondisiPallet::class, 'id_pallet')->latestOfMany('id_kondisi_pallet');
    }
}