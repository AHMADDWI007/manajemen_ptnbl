<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- TAMBAHKAN INI

class Maturasi extends Model
{
    use HasFactory;
    
    // (fillable Anda)
    protected $fillable = ['uraian', 'stok_awal', 'tgl_masuk', 'umur', 'diolah', 'mutasi', 'masuk_hi', 'stok_akhir', 'asal_bokar', 'keterangan', 'id_hasil_uji_maturasi']; // Pastikan 'id_hasil_uji_maturasi' ada di fillable
    
    protected $casts = ['tgl_masuk' => 'date'];

    // (Relasi Anda ke pengolahan_maturasi)
    public function riwayatPengolahan(): HasMany {
        return $this->hasMany(PengolahanMaturasi::class, 'maturasi_id');
    }

    // ==========================================================
    // TAMBAHKAN FUNGSI RELASI INI
    // ==========================================================
    public function hasilUjiMaturasi(): BelongsTo
    {
        // Ini akan menghubungkan 'id_hasil_uji_maturasi' (di tabel ini)
        // ke 'id' di tabel 'hasil_uji_maturasi'
        return $this->belongsTo(HasilUjiMaturasi::class, 'id_hasil_uji_maturasi');
    }
    // ==========================================================
    public function hasilUjiBokarDiolah()
{
    return $this->hasOne(HasilUjiBokarDiolah::class, 'bak_maturasi', 'uraian');
}

    public function hasilUji()
     {
     // Ini akan memanggil relasi 'hasilUjiMaturasi'
     // tapi saat di-serialize ke JSON, namanya akan menjadi 'hasil_uji'
    return $this->hasilUjiMaturasi();
     }

}

