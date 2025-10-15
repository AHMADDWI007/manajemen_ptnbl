<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUjiTroli extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = 'hasil_uji_troli';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     * Kolom-kolom ini akan diisi oleh aplikasi mobile Anda melalui API.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tanggal',
        'no_trolly',
        'k3',
        'po',
        'pa',
        'pri',
        'jam_sample',
        'lama_pengeringan',
    ];

    /**
     * Kita akan menggunakan timestamps (created_at dan updated_at)
     * jadi baris 'public $timestamps = false;' dihapus.
     * Timestamps sangat berguna untuk melacak kapan data dibuat atau diubah.
     */
}