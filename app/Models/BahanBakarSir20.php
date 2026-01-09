<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BahanBakarSir20 extends Model
{
    use HasFactory;

    protected $table = 'bahan_bakar_sir20';
    protected $primaryKey = 'id_bahan_bakar_sir20';

    protected $fillable = [
        'id_produksi_sir20', // Foreign Key
        'bahan_bakar',       // Solar, Batu Bara, Cangkang
        'digunakan',
    ];

    // Relasi Balik ke Header
    public function produksi(): BelongsTo
    {
        return $this->belongsTo(ProduksiSir20::class, 'id_produksi_sir20');
    }
}