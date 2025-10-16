<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bokar extends Model
{
    use HasFactory;

    protected $table = 'bokar';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Kolom baru ditambahkan di sini.
     */
    protected $fillable = [
        'tanggal',
        'no_kamar',
        'berat_awal',
        'berat_truck',
        'berat_basah',
        'k3_lab',
        'berat_kering',
        'total_ds',       // Ditambahkan
        'total_pt',       // Ditambahkan
        'jumlah',         // Ditambahkan
    ];
}

