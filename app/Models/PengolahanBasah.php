<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengolahanBasah extends Model
{
    use HasFactory;

    /**
     * Nama tabel database
     */
    protected $table = 'pengolahan_basah';

    /**
     * Kolom yang boleh diisi
     */
    protected $fillable = [
        'tanggal',
        'bak_maturasi',
        'jenis',
        'berat_truck',
        'berat_timbang',
        'netto_basah',
        'k3',
        'netto_kering',
    ];

    /**
     * (TAMBAHAN PENTING) Tipe data casting.
     * Ini memastikan kalkulasi angka selalu benar.
     */
    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'berat_truck' => 'double',
        'berat_timbang' => 'double',
        'netto_basah' => 'double',
        'k3' => 'double',
        'netto_kering' => 'double',
    ];
}