<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AktualTemperatureSir20 extends Model
{
    use HasFactory;

    protected $table = 'aktual_temperature_sir20';
    protected $primaryKey = 'id_aktual_temperature_sir20';

    protected $fillable = [
        'id_produksi_sir20', // Foreign Key
        'jenis',             // Burner 1, Burner 2, Cycle Time
        'nilai_start',
        'nilai_end',
    ];

    // Relasi Balik ke Header
    public function produksi(): BelongsTo
    {
        return $this->belongsTo(ProduksiSir20::class, 'id_produksi_sir20');
    }
}