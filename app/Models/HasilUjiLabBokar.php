<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiLabBokar extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'hasil_uji_lab_bokar';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tanggal',
        'suplier',      // Pastikan ejaan ini ('suplier') sama persis dengan nama kolom di database Anda
        'no_sampel',    // Pastikan ini ('no_sampel') sama persis dengan nama kolom di database Anda
        'k3',
        'dirt',
        'ask',          // Pastikan ejaan ini ('ask') sama persis dengan nama kolom di database Anda (atau ganti ke 'ash' jika perlu)
        'po',
        'pa',
        'pri',
    ];

    /**
     * Casts tipe data atribut.
     * Direkomendasikan untuk tanggal dan desimal.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'date',      // Otomatis konversi ke objek Carbon saat diambil
        'k3'      => 'decimal:2', // Tentukan jumlah desimal
        'dirt'    => 'decimal:2',
        'ask'     => 'decimal:2', // Atau 'ash' => 'decimal:2'
        'po'      => 'decimal:2',
        'pa'      => 'decimal:2',
        'pri'     => 'decimal:2',
    ];
}