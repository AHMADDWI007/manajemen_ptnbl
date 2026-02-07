<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pallet extends Model
{
    protected $table = 'pallet';
    protected $primaryKey = 'id_pallet';
    protected $guarded = [];

    // Relasi ke History Lokasi
    public function historyLokasi() {
        return $this->hasMany(LokasiPallet::class, 'id_pallet');
    }

    // Relasi ke History Mutu
    public function historyMutu() {
        return $this->hasMany(KondisiPallet::class, 'id_pallet');
    }

    // 🔥 PERBAIKAN: Tambahkan parameter nama kolom PK di latestOfMany()
    public function latestLokasi() {
        // Kita beri tahu: "Urutkan berdasarkan 'id_lokasi_pallet', bukan 'id'"
        return $this->hasOne(LokasiPallet::class, 'id_pallet')->latestOfMany('id_lokasi_pallet');
    }

    // 🔥 PERBAIKAN: Tambahkan parameter nama kolom PK di latestOfMany()
    public function latestMutu() {
        // Kita beri tahu: "Urutkan berdasarkan 'id_kondisi_pallet', bukan 'id'"
        return $this->hasOne(KondisiPallet::class, 'id_pallet')->latestOfMany('id_kondisi_pallet');
    }
}