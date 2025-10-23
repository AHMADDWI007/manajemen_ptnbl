<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiSIR20 extends Model
{
    use HasFactory;

    // Nama tabel sesuai database
    protected $table = 'hasil_uji_sir_20';

    // Kolom yang bisa diisi massal
    protected $fillable = [
        'tanggal',
        'jenis_kemasan',
        'no_palet',
        'po',
        'pa',
        'pri',
        'dirt',
        'ash',
        'vm',
        'money',
        'nitrogen'
    ];

    // Jika menggunakan tanggal secara otomatis
    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at'
    ];
}
