<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon; // <-- Import Carbon

class Maturasi extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'maturasis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uraian',
        'stok_awal',
        'tgl_masuk', // Tanggal bokar asli masuk
        'umur',
        'diolah',
        'mutasi',
        'masuk_hi',
        'stok_akhir',
        'asal_bokar',
        'keterangan',
        // 'tanggal_input' tidak ada di fillable karena tidak disimpan (Opsi 1)
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Pastikan tgl_masuk selalu di-cast sebagai objek datetime (Carbon)
        // Ini membantu mencegah error saat memanggil method tanggal padanya
        'tgl_masuk' => 'datetime',
        // Anda juga bisa menambahkan cast untuk kolom numerik jika perlu
        'stok_awal' => 'decimal:2', // Contoh jika ingin presisi 2 desimal
        'diolah' => 'decimal:2',
        'mutasi' => 'decimal:2',
        'masuk_hi' => 'decimal:2',
        'stok_akhir' => 'decimal:2',
        'umur' => 'integer',
    ];
}