<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiMaturasi extends Model
{
    use HasFactory;

    protected $table = 'hasil_uji_maturasi'; // sesuaikan nama tabel

    protected $fillable = [
        'tanggal',
        'jam',
        'no_kamar',
        'hasil_uji',
        'status',
    ];
}
