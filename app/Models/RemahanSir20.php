<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemahanSir20 extends Model
{
    use HasFactory;

    // 1. Nama Tabel
    protected $table = 'remahan_sir20';

    // 2. Primary Key Custom
    protected $primaryKey = 'id_remahan_sir20';

    // 3. Kolom yang boleh diisi
    protected $fillable = [
        'id_produksi_sir20', // Foreign Key
        'ruang_maturasi',
        'berat',
        'umur',
    ];

    // 4. Relasi Balik ke Header (Produksi)
    public function produksi(): BelongsTo
    {
        return $this->belongsTo(ProduksiSir20::class, 'id_produksi_sir20');
    }
}