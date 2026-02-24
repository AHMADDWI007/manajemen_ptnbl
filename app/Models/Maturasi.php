<?php

namespace App\Models;

use App\Models\HasilUjiLabBokarDiolah;
use App\Models\HasilUjiLabMaturasi;
use App\Models\PengolahanBasah;
use App\Models\PengolahanMaturasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maturasi extends Model
{
    use HasFactory;
    
    protected $table = 'maturasi';
    protected $primaryKey = 'id_maturasi';
    protected $guarded = [];
    protected $casts = ['tgl_masuk' => 'date'];

    public function pengolahanBasah(): HasMany
    {
        return $this->hasMany(PengolahanBasah::class, 'id_maturasi'); // Sesuaikan foreign key
    }

    public function hasilUjiLabMaturasi(): HasMany // Sesuaikan nama relasi agar konsisten pakai Lab
    {
        return $this->hasMany(HasilUjiLabMaturasi::class, 'id_maturasi');
    }

    public function riwayatPengolahan(): HasMany
    {
        return $this->hasMany(PengolahanMaturasi::class, 'id_maturasi');
    }
    
    public function hasilUjiLabBokarDiolah(): HasMany // Sesuaikan nama relasi
    {
        return $this->hasMany(HasilUjiLabBokarDiolah::class, 'id_maturasi');
    }
}